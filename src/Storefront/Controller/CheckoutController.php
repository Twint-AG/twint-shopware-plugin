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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Twint\Command\TwintPollCommand;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
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
        private readonly KernelInterface $kernel,
        private readonly ExpressCheckoutServiceInterface $checkoutService,
        private CryptoHandler $cryptoService,
        private readonly PairingRepository $paringLoader,
        private PaymentService $paymentService,
        private readonly CartService $cartService,
        private readonly MonitoringService $monitor
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

    /**
     * @throws PairingException
     */
    #[Route(path: '/payment/monitoring/{paringHash}', name: 'frontend.twint.monitoring', defaults: [
        'XmlHttpRequest' => true,
    ], methods: ['GET'])]
    public function monitor(Request $request, SalesChannelContext $context): Response
    {
        $pairingHash = $request->get('paringHash');
        try {
            $pairingUuid = $this->cryptoService->unHash($pairingHash);
            $pairing = $this->paringLoader->load($pairingUuid, $context->getContext());

            if(!$pairing->isFinished() && !$this->isRunning($pairing->getPid())) {
                $process = new Process(["php", $this->kernel->getProjectDir() . "/bin/console", TwintPollCommand::COMMAND, $pairingUuid]);
                $process->setOptions(['create_new_console' => true]);
                $process->disableOutput();
                $process->start();

                $this->paringLoader->update([['id' => $pairing->getId(), 'pid' => $process->getPid()]]);
                $pairing = $this->paringLoader->load($pairingUuid, $context->getContext());
            }
        } catch (Throwable $e) {
            $this->addFlash(self::DANGER, $this->trans('twintPayment.error.pairingNotFound'));
            return $this->redirectToRoute('frontend.account.order.page');
        }

        if ($pairing->isFinished()) {
            $data = [
                'completed' => true,
                'orderId' => $pairing->getStatus() === PairingEntity::STATUS_CANCELED ? null : $pairing->getOrderId(),
            ];

            if(!empty($data['orderId'])){
                $data['thank-you'] = $this->thankYouPage($pairing, $context)->getContent();
            }

            return $this->json($data);
        }

        return $this->json([
            'completed' => false,
        ]);
    }

    protected function isRunning(int|null $processId): bool
    {
        if(is_null($processId))
            return false;

        if(function_exists('posix_getpgid')) {
            return (bool) posix_getpgid($processId);
        }

        return true;
    }

    #[Route(path: '/payment/express/{paringHash}', name: 'frontend.twint.express', methods: ['GET'], defaults: [
        'XmlHttpRequest' => true,
    ])]
    public function express(Request $request, SalesChannelContext $context): Response
    {
        $pairingHash = $request->get('paringHash');
        try {
            $pairingUUid = $this->cryptoService->unHash($pairingHash);
            $pairing = $this->paringLoader->load($pairingUUid, $context->getContext());
            $pairing = $this->paringLoader->fetchCart($pairing, $context);
        } catch (Exception $e) {
            $this->addFlash(self::DANGER, $this->trans('twintPayment.error.pairingNotFound'));
            return $this->redirectToRoute('frontend.account.order.page');
        }

        if ($pairing->getOrderId()) {
            return $this->thankYouPage($pairing, $context);
        }

        $options = new QROptions(
            [
                'eccLevel' => QRCode::ECC_L,
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'version' => 5,
            ]
        );
        $qrcode = (new QRCode($options))->render($pairing->getToken());

        return $this->renderStorefront('@TwintPayment/storefront/page/express-payment.html.twig', [
            'pairingHash' => $pairingHash,
            'orderNumber' => 'CART',
            'qrCode' => $qrcode,
            'pairingToken' => $pairing->getToken(),
            'amount' => $pairing->getCart() // @phpstan-ignore-line
                ->getPrice()
                ->getPositionPrice(),
            'payLinks' => [],
        ]);
    }
    
    protected function thankYouPage(PairingEntity $pairing, SalesChannelContext $context): RedirectResponse|Response
    {
        $page = new CheckoutFinishPage();
        $this->paringLoader->fetchOrder($pairing, $context);

        if (!($pairing->getOrder()  instanceof OrderEntity)) {
            $this->addFlash(self::DANGER, $this->trans('twintPayment.error.orderNotFound'));
            return $this->redirectToRoute('frontend.account.order.page');
        }

        $page->setOrder($pairing->getOrder());

        return $this->renderStorefront('@TwintPayment/storefront/page/express-finish.html.twig', [
            'page' => $page,
        ]);
    }

    /**
     * @throws PairingException
     */
    private function getPairingContent(string $pairingHash, SalesChannelContext $context): string
    {
        $pairingUUid = $this->cryptoService->unHash($pairingHash);
        $pairing = $this->paringLoader->load($pairingUUid, $context->getContext());
        $pairing = $this->paringLoader->fetchCart($pairing, $context);

        $options = new QROptions(
            [
                'eccLevel' => QRCode::ECC_L,
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'version' => 5,
            ]
        );
        $qrcode = (new QRCode($options))->render($pairing->getToken()); // @phpstan-ignore-line

        // @phpstan-ignore-next-line
        return $this->renderStorefront('@TwintPayment/storefront/page/express-payment.html.twig', [
            'pairingHash' => $pairingHash,
            'orderNumber' => 'CART',
            'qrCode' => $qrcode,
            'pairingToken' => $pairing->getToken(), // @phpstan-ignore-line
            'amount' => $pairing->getCart() // @phpstan-ignore-line
                ->getPrice()
                ->getPositionPrice(),
            'payLinks' => $this->paymentService->getPayLinks(
                $pairing->getToken(), // @phpstan-ignore-line
                $context->getSalesChannel() // @phpstan-ignore-line
                    ->getId()
            ),
        ])->getContent();
    }
}
