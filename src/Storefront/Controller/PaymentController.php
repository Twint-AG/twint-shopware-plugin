<?php

declare(strict_types=1);

namespace Twint\Storefront\Controller;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Http\Client\Common\Exception\HttpClientNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Service\PairingService;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;

#[Route(defaults: [
    '_routeScope' => ['storefront'],
])]
class PaymentController extends StorefrontController
{
    public function __construct(
        private EntityRepository $pairingRepository,
        private CryptoHandler $cryptoService,
        private PaymentService $paymentService,
    ) {
    }

    #[Route(path: '/payment/waiting/{pairingId}', name: 'frontend.twint.waiting', methods: ['GET'])]
    public function showWaiting(Request $request, SalesChannelContext $context): Response
    {
        $hash = $request->get('pairingId');
        try {
            $pairing = $this->getPairing($hash, $context);
        } catch (Exception $e) {
            return $this->redirectToRoute('frontend.account.order.page');
        }

        if ($pairing->isSuccess()) {
            $this->addFlash(self::SUCCESS, $this->trans('twintPayment.message.successPayment'));
            return $this->redirectToRoute('frontend.checkout.finish.page', [
                'orderId' => $pairing->getOrderId(),
            ]);
        }

        if ($pairing->isFailed()) {
            return $this->redirectToRoute('frontend.account.edit-order.page', [
                'orderId' => $pairing->getOrderId(),
                'error-code' => 'CHECKOUT__TWINT_PAYMENT_DECLINED',
            ]);
        }

        $options = new QROptions(
            [
                'eccLevel' => QRCode::ECC_L,
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'version' => 5,
            ]
        );

        return $this->renderStorefront('@TwintPayment/storefront/page/waiting.html.twig', [
            'pairing' => $hash,
            'qrCode' => (new QRCode($options))->render($pairing->getToken()),
            'pairingToken' => $pairing->getToken(),
            'amount' => $pairing->getAmount(),
            'payLinks' => $this->paymentService->getPayLinks($pairing->getToken(), $pairing->getSalesChannelId()),
        ]);
    }

    private function getPairing(string $hash, SalesChannelContext $context): PairingEntity
    {
        $pairingId = $this->cryptoService->unHash($hash);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $pairingId));
        $criteria->addAssociation('order.transactions.stateMachineState');

        /** @var PairingEntity $pairing */
        $pairing = $this->pairingRepository->search($criteria, $context->getContext())
            ->first();

        if (!$pairing instanceof PairingEntity) {
            throw new HttpClientNotFoundException($pairingId);
        }

        return $pairing;
    }
}
