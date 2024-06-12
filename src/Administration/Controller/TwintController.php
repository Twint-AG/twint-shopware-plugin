<?php

declare(strict_types=1);

namespace Twint\Administration\Controller;

use Exception;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twint\Core\Handler\ReversalHistory\ReversalHistoryWriterInterface;
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
                    'message' => 'Certificate validation successful ',
                    'data' => [
                        'certificate' => $this->encryptor->encrypt($certificate->content()),
                        'passphrase' => $this->encryptor->encrypt($certificate->passphrase()),
                    ],
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => 'Invalid certificate file ',
                'errorCode' => $certificate,
            ], 400);
        }

        return $this->json([
            'success' => false,
            'message' => 'Please upload a valid certificate file ',
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
                'error' => 'The refund amount cannot be negative.',
            ]);
        }
        try {
            $order = $this->paymentService->getOrder($orderId, new Context(new SystemSource()));
            $amountMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $amount);
            $refundableAmount = $order->getAmountTotal() - $this->paymentService->getTotalReversal($order->getId());
            $refundableAmountMoney = new Money($order->getCurrency()?->getIsoCode() ?? Money::CHF, $refundableAmount);
            if ($refundableAmountMoney->compare($amountMoney) < 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'The refund amount cannot exceed ' . $refundableAmount . ' ' . $order->getCurrency()?->getIsoCode(),
                ]);
            }
            $twintReverseOrder = $this->paymentService->reverseOrder($order, $amount);
            if ($twintReverseOrder instanceof Order) {
                if (empty($reason)) {
                    $reason = ' ';
                }
                $this->reversalHistoryWriter->write(
                    $orderId,
                    $twintReverseOrder->merchantTransactionReference()
                        ->__toString(),
                    $twintReverseOrder->amount()
                        ->amount(),
                    $twintReverseOrder->amount()
                        ->currency(),
                    $reason
                );
                return $this->json([
                    'success' => true,
                ]);
            }
            return $this->json([
                'success' => false,
                'error' => 'Refund cannot be processed for this order',
            ]);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
