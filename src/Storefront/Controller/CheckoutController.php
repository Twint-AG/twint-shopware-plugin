<?php

declare(strict_types=1);

namespace Twint\Storefront\Controller;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;
use Twint\ExpressCheckout\Exception\PairingException;
use Twint\ExpressCheckout\Repository\PairingRepository;
use Twint\ExpressCheckout\Service\ExpressCheckoutServiceInterface;

#[Route(defaults: [
    '_routeScope' => ['storefront'],
])]
class CheckoutController extends StorefrontController
{
    public function __construct(
        private readonly ExpressCheckoutServiceInterface $checkoutService,
        private CryptoHandler $cryptoService,
        private readonly PairingRepository $paringLoader,
        private PaymentService $paymentService
    ) {
    }

    #[Route(path: '/twint/express-checkout', name: 'frontend.twint.express-checkout', methods: ['POST'], defaults: [
        'XmlHttpRequest' => true,
        'csrf_protected' => false,
    ])]
    public function expressCheckout(Request $request, SalesChannelContext $context): Response
    {
        $pairing = $this->checkoutService->pairing($context, $request);
        return $this->json([
            'success' => true,
            'redirectUrl' => '/payment/express/' . $this->cryptoService->hash($pairing->pairingUuid()->__toString()),
            'content' => $this->getPairingContent(
                $this->cryptoService->hash((string) $pairing->pairingUuid()),
                $context
            ),
        ]);
    }

    #[Route(path: '/payment/monitoring/{paringHash}', name: 'frontend.twint.monitoring', methods: ['GET'], defaults: [
        'XmlHttpRequest' => true,
    ])]
    public function monitor(Request $request, SalesChannelContext $context): Response
    {
        $pairingHash = $request->get('paringHash');
        try {
            $pairingUUid = $this->cryptoService->unHash($pairingHash);
            $paring = $this->paringLoader->load($pairingUUid, $context);
        } catch (Exception $e) {
            $this->addFlash(self::DANGER, $this->trans('twintPayment.error.pairingNotFound'));
            return $this->redirectToRoute('frontend.account.order.page');
        }

        if ($paring->getOrderId()) {
            return $this->json([
                'completed' => true,
                'orderId' => $paring->getOrderId(),
            ]);
        }

        return $this->json([
            'completed' => false,
        ]);
    }

    #[Route(path: '/payment/express/{paringHash}', name: 'frontend.twint.express', methods: ['GET'], defaults: [
        'XmlHttpRequest' => true,
    ])]
    public function express(Request $request, SalesChannelContext $context): Response
    {
        $pairingHash = $request->get('paringHash');
        try {
            $pairingUUid = $this->cryptoService->unHash($pairingHash);
            $paring = $this->paringLoader->load($pairingUUid, $context);
        } catch (Exception $e) {
            $this->addFlash(self::DANGER, $this->trans('twintPayment.error.pairingNotFound'));
            return $this->redirectToRoute('frontend.account.order.page');
        }

        if ($paring->getOrderId()) {
            $page = new CheckoutFinishPage();
            $this->paringLoader->fetchOrder($paring, $context);

            if (!($paring->getOrder()  instanceof OrderEntity)) {
                $this->addFlash(self::DANGER, $this->trans('twintPayment.error.orderNotFound'));
                return $this->redirectToRoute('frontend.account.order.page');
            }

            $page->setOrder($paring->getOrder());

            return $this->renderStorefront('@TwintPayment/storefront/page/express-finish.html.twig', [
                'page' => $page,
            ]);
        }

        $options = new QROptions(
            [
                'eccLevel' => QRCode::ECC_L,
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'version' => 5,
            ]
        );
        $qrcode = (new QRCode($options))->render($paring->getToken());

        return $this->renderStorefront('@TwintPayment/storefront/page/express-payment.html.twig', [
            'pairingHash' => $pairingHash,
            'orderNumber' => 'CART',
            'qrCode' => $qrcode,
            'pairingToken' => $paring->getToken(),
            'amount' => $paring->getCart() // @phpstan-ignore-line
                ->getPrice()
                ->getPositionPrice(),
            'payLinks' => [],
        ]);
    }

    /**
     * @throws PairingException
     */
    private function getPairingContent(string $pairingHash, SalesChannelContext $context): string
    {
        $pairingUUid = $this->cryptoService->unHash($pairingHash);
        $paring = $this->paringLoader->load($pairingUUid, $context);

        $options = new QROptions(
            [
                'eccLevel' => QRCode::ECC_L,
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'version' => 5,
            ]
        );
        $qrcode = (new QRCode($options))->render($paring->getToken()); // @phpstan-ignore-line

        // @phpstan-ignore-next-line
        return $this->renderStorefront('@TwintPayment/storefront/page/express-payment.html.twig', [
            'pairingHash' => $pairingHash,
            'orderNumber' => 'CART',
            'qrCode' => $qrcode,
            'pairingToken' => $paring->getToken(), // @phpstan-ignore-line
            'amount' => $paring->getCart() // @phpstan-ignore-line
                ->getPrice()
                ->getPositionPrice(),
            'payLinks' => $this->paymentService->getPayLinks(
                $paring->getToken(), // @phpstan-ignore-line
                $context->getSalesChannel() // @phpstan-ignore-line
                    ->getId()
            ),
        ])->getContent();
    }
}
