<?php

declare(strict_types=1);

namespace Twint\Storefront\Controller;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Twint\Core\Repository\PairingRepository;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;
use Twint\ExpressCheckout\Exception\PairingException;
use Twint\ExpressCheckout\Service\ExpressCheckoutServiceInterface;
use Twint\ExpressCheckout\Service\Monitoring\MonitoringService;
use Twint\ExpressCheckout\Service\PairingService;

#[Route(defaults: [
    '_routeScope' => ['storefront'],
])]
class CheckoutController extends StorefrontController
{
    public function __construct(
        private readonly ExpressCheckoutServiceInterface $checkoutService,
        private CryptoHandler $cryptoService,
        private readonly PairingRepository $paringLoader,
        private PaymentService $paymentService,
        private readonly CartService $cartService,
        private readonly MonitoringService $monitor,
    ) {
    }

    #[Route(path: '/twint/express-checkout', name: 'frontend.twint.express-checkout', methods: ['POST'], defaults: [
        'XmlHttpRequest' => true,
        'csrf_protected' => false,
    ])]
    public function expressCheckout(Request $request, SalesChannelContext $context): Response
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);
        $useCart = $request->request->get('useCart', false);
        if ($cart->getLineItems()->count() >= 1 && !$useCart) {
            $this->addFlash(self::SUCCESS, $this->trans('twintPayment.notice.hasProductInCart'));
            return $this->json([
                'success' => true,
                'needAddProductToCart' => true,
            ]);
        }
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
        } catch (Throwable $e) {
            $this->addFlash(self::DANGER, $this->trans('twintPayment.error.pairingNotFound'));
            return $this->redirectToRoute('frontend.account.order.page');
        }

        $this->monitor->monitorOne($paring);

        if (in_array($paring->getStatus(), [PairingService::STATUS_DONE, PairingService::STATUS_CANCELED], true)) {
            return $this->json([
                'completed' => true,
                'orderId' => $paring->getStatus() === PairingService::STATUS_CANCELED ? null : $paring->getOrderId(),
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
