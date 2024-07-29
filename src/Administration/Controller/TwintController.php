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
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
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

        if ($file instanceof UploadedFile) {
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
        $merchantId = $request->get('merchantId') ?? '';
        $testMode = $request->get('testMode') ?? false;

        $valid = $this->validator->validate($certificate, $merchantId, $testMode);

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
        if ($amount <= 0) {
            return $this->json([
                'success' => false,
                $this->translator->trans('twintPayment.administration.refund.error.negativeAmount'),
            ]);
        }

        try {
            $order = $this->orderService->getOrder($orderId, new Context(new SystemSource()));
            if (($twintOrder = $this->orderService->getTwintOrder($order)) instanceof Order) {
                $amountMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $amount);
                $refundableAmount = $this->rounding->mathRound(
                    $twintOrder->amount()
                        ->amount() - $this->paymentService->getTotalReversal($order->getId()),
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
    public function status(string $orderId, Request $request, Context $context): Response
    {
        try {
            $order = $this->orderService->getOrder($orderId, new Context(new SystemSource()));
            $twintStatusOrder = $this->paymentService->monitorOrder($order);
            if ($twintStatusOrder instanceof Order) {
                return $this->json([
                    'success' => true,
                    'order' => json_encode($twintStatusOrder),
                ]);
            }
            return $this->json([
                'success' => false,
            ]);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
