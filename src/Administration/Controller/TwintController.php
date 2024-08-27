<?php

declare(strict_types=1);

namespace Twint\Administration\Controller;

use Exception;
use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Handler\ReversalHistory\ReversalHistoryWriterInterface;
use Twint\Core\Service\OrderService;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CertificateHandler;
use Twint\Core\Util\CredentialValidatorInterface;
use Twint\Core\Util\CryptoHandler;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;

#[Package('checkout')]
#[Route(defaults: [
    '_routeScope' => ['api'],
])]
class TwintController extends AbstractController
{
    public const MAX_PASSWORD_LENGTH = 512;

    public const MAX_CERTIFICATE_FILE_SIZE = 128;

    public const MAX_REFUND_DESCRIPTION_LENGTH = 127;

    private CryptoHandler $encryptor;

    private CredentialValidatorInterface $validator;

    private PaymentService $paymentService;

    private ReversalHistoryWriterInterface $reversalHistoryWriter;

    private TranslatorInterface $translator;

    private CashRounding $rounding;

    private OrderService $orderService;

    public function setEncryptor(CryptoHandler $encryptor): void
    {
        $this->encryptor = $encryptor;
    }

    public function setValidator(CredentialValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    public function setPaymentService(PaymentService $paymentService): void
    {
        $this->paymentService = $paymentService;
    }

    public function setReversalHistoryWriter(ReversalHistoryWriterInterface $reversalHistoryWriter): void
    {
        $this->reversalHistoryWriter = $reversalHistoryWriter;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function setCashRounding(CashRounding $rounding): void
    {
        $this->rounding = $rounding;
    }

    public function setOrderService(OrderService $orderService): void
    {
        $this->orderService = $orderService;
    }

    #[Route(path: '/api/_actions/twint/extract-pem', name: 'api.action.twint.extract_pem', methods: ['POST'])]
    public function extractPem(Request $request, Context $context): Response
    {
        $file = $request->files->get('file');
        $password = $request->get('password') ?? '';
        $validator = Validation::createValidator();
        $passwordViolations = $validator->validate(
            $password,
            [
                new NotBlank(),
                new Length([
                    'max' => self::MAX_PASSWORD_LENGTH,
                ]),
            ]
        );
        if (count($passwordViolations) > 0) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('twintPayment.administration.extractPem.error.invalidFile'),
                'errorCode' => 'ERROR_INVALID_PASSPHRASE',
            ], 400);
        }
        if ($file instanceof UploadedFile) {
            $fileViolations = $validator->validate(
                $file,
                [
                    new NotNull(),
                    new File(
                        [
                            'maxSize' => self::MAX_CERTIFICATE_FILE_SIZE * 1024,
                            'mimeTypes' => ['application/x-pkcs12', 'application/octet-stream'],
                        ]
                    ),
                ]
            );
            if (count($fileViolations) > 0) {
                return $this->json([
                    'success' => false,
                    'message' => $this->translator->trans('twintPayment.administration.extractPem.error.invalidFile'),
                    'errorCode' => 'invalid',
                ], 400);
            }
            $content = file_get_contents($file->getPathname());

            $extractor = new CertificateHandler();
            $certificate = $extractor->read((string) $content, $password);

            if ($certificate instanceof Pkcs12Certificate) {
                return $this->json([
                    'success' => true,
                    'message' => $this->translator->trans('twintPayment.administration.extractPem.success.message'),
                    'data' => [
                        'certificate' => $this->encryptor->encrypt($certificate->content()),
                        'passphrase' => $this->encryptor->encrypt($certificate->passphrase()),
                    ],
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('twintPayment.administration.extractPem.error.invalidFile'),
                'errorCode' => $certificate,
            ], 400);
        }

        return $this->json([
            'success' => false,
            'message' => $this->translator->trans('twintPayment.administration.extractPem.error.emptyFile'),
        ], 400);
    }

    #[Route(
        path: '/api/_actions/twint/validate-api-credential',
        name: 'api.action.twint.validate_credential',
        methods: ['POST']
    )]
    public function validate(Request $request, Context $context): Response
    {
        $certificate = $request->get('cert') ?? [];
        $storeUuid = $request->get('storeUuid') ?? '';
        $testMode = $request->get('testMode') ?? false;

        $valid = $this->validator->validate($certificate, $storeUuid, $testMode);

        return $this->json([
            'success' => $valid,
        ]);
    }

    #[Route(path: '/api/_actions/twint/refund', name: 'api.action.twint.refund', methods: ['POST'])]
    public function refund(Request $request, Context $context): Response
    {
        $orderId = $request->get('orderId') ?? '';
        $reason = $request->get('reason') ?? '';
        $amount = (float) ($request->get('amount') ?? 0);
        $validator = Validation::createValidator();
        $reasonViolations = $validator->validate(
            $reason,
            [
                new Length([
                    'max' => self::MAX_REFUND_DESCRIPTION_LENGTH,
                ]),
            ]
        );
        if (count($reasonViolations) > 0) {
            return $this->json([
                'success' => false,
                'error' => $this->translator->trans('twintPayment.administration.refund.error.fail'),
            ]);
        }
        if ($amount <= 0) {
            return $this->json([
                'success' => false,
                $this->translator->trans('twintPayment.administration.refund.error.negativeAmount'),
            ]);
        }

        try {
            $order = $this->orderService->getOrder($orderId, new Context(new SystemSource()));
            if (($pairing = $this->orderService->getPairing($orderId)) instanceof PairingEntity) {
                $amountMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $amount);
                $refundableAmount = $this->rounding->mathRound(
                    $pairing->getAmount() - $this->paymentService->getTotalReversal($order->getId()),
                    $order->getItemRounding() ?? $context->getRounding()
                );
                $refundableAmountMoney = new Money(
                    $order->getCurrency()?->getIsoCode() ?? Money::CHF,
                    $refundableAmount
                );
                if ($refundableAmountMoney->compare($amountMoney) < 0) {
                    return $this->json([
                        'success' => false,
                        'error' => $this->translator->trans('twintPayment.administration.refund.error.exceededAmount', [
                            '%amount%' => $refundableAmount,
                            '%currency%' => $order->getCurrency()?->getIsoCode(),
                        ]),
                    ]);
                }

                $twintReverseOrder = $this->paymentService->reverseOrder($order, $amount);
                if ($twintReverseOrder instanceof Order) {
                    $this->reversalHistoryWriter->write(
                        $orderId,
                        $twintReverseOrder->merchantTransactionReference()
                            ->__toString(),
                        $twintReverseOrder->amount()
                            ->amount(),
                        $twintReverseOrder->amount()
                            ->currency(),
                        $reason,
                        $context
                    );
                    return $this->json([
                        'success' => true,
                        'action' => $this->paymentService->getNextAction($order),
                    ]);
                }
                return $this->json([
                    'success' => false,
                    'error' => $this->translator->trans('twintPayment.administration.refund.error.fail'),
                ]);
            }

            return $this->json([
                'success' => false,
                'error' => $this->translator->trans('twintPayment.administration.refund.error.missingResponse'),
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    #[Route(path: '/api/_actions/twint/order/{orderId}/status', name: 'api.action.twint.order.status', methods: [
        'GET',
    ])]
    public function status(string $orderId): Response
    {
        try {
            $pairing = $this->orderService->getPairing($orderId);
            if (!$pairing instanceof PairingEntity) {
                throw new Exception('Record not found');
            }

            return $this->json([
                'success' => $this->paymentService->monitorOrder($pairing),
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/api/_actions/twint/order/{orderId}/pairing', name: 'api.action.twint.order.pairing', methods: [
        'GET',
    ])]
    public function pairing(string $orderId): Response
    {
        $pairing = $this->orderService->getPairing($orderId);

        return $this->json([
            'pairing' => $pairing,
        ]);
    }
}
