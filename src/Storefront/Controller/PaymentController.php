<?php

declare(strict_types=1);

namespace Twint\Storefront\Controller;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twint\Core\Service\PaymentService;
use Twint\Core\Util\CryptoHandler;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderStatus;
use Twint\Util\OrderCustomFieldInstaller;

#[Route(defaults: [
    '_routeScope' => ['storefront'],
])]
class PaymentController extends StorefrontController
{
    public function __construct(
        private EntityRepository $orderRepository,
        private CryptoHandler $cryptoService,
        private PaymentService $paymentService
    ) {
    }

    #[Route(path: '/payment/waiting/{orderNumber}', name: 'frontend.twint.waiting', methods: ['GET'])]
    public function showWaiting(Request $request, Context $context): Response
    {
        $orderNumber = $request->get('orderNumber');
        try {
            $orderNumber = $this->cryptoService->unHash($orderNumber);
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
            $criteria->addAssociation('orderCustomer.customer')
                ->addAssociation('transactions.paymentMethod')
                ->addAssociation('transactions.stateMachineState')
                ->addAssociation('lineItems')
                ->addAssociation('currency')
                ->addAssociation('addresses.country')
                ->addAssociation('customFields');
            /** @var OrderEntity|null $order */
            $order = $this->orderRepository->search($criteria, $context)
                ->first();
            if (empty($order)) {
                throw OrderException::orderNotFound($orderNumber);
            }
        } catch (Exception $e) {
            $this->addFlash(self::DANGER, $this->trans('twintPayment.error.orderNotFound'));
            return $this->redirectToRoute('frontend.account.order.page');
        }
        if ($this->paymentService->isOrderPaid($order)) {
            $this->addFlash(self::SUCCESS, $this->trans('twintPayment.message.successPayment'));
            return $this->redirectToRoute('frontend.checkout.finish.page', [
                'orderId' => $order->getId(),
            ]);
        } elseif ($this->paymentService->isCancelPaid($order)) {
            $this->addFlash(self::DANGER, $this->trans('twintPayment.message.cancelPayment'));
            return $this->redirectToRoute('frontend.account.edit-order.page', [
                'orderId' => $order->getId(),
                'error-code' => 'CHECKOUT__TWINT_PAYMENT_DECLINED',
            ]);
        }
        $twintApiResponse = json_decode(
            $order->getCustomFields()[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] ?? '{}',
            true
        );
        $qrcode = '';
        if ($twintApiResponse) {
            $options = new QROptions(
                [
                    'eccLevel' => QRCode::ECC_L,
                    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                    'version' => 5,
                ]
            );
            $qrcode = (new QRCode($options))->render($twintApiResponse['pairingToken']);
        }
        return $this->renderStorefront('@TwintPayment/storefront/page/waiting.html.twig', [
            'orderNumber' => $orderNumber,
            'qrCode' => $qrcode,
            'pairingToken' => $twintApiResponse['pairingToken'] ?? '',
            'amount' => $order->getPrice()
                ->getTotalPrice(),
            'payLinks' => $this->paymentService->getPayLinks(
                $twintApiResponse['pairingToken'] ?? '',
                $order->getSalesChannelId()
            ),
        ]);
    }

    #[Route(path: '/payment/order/{orderNumber}', name: 'frontend.twint.order', defaults: [
        'XmlHttpRequest' => true,
        'csrf_protected' => false,
    ], methods: ['GET'])]
    public function order(Request $request, Context $context): JsonResponse
    {
        $orderNumber = $request->get('orderNumber');
        $result = [
            'reload' => false,
        ];
        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
            $criteria->addAssociation('transactions.stateMachineState');
            $criteria->addAssociation('currency');
            /** @var OrderEntity|null $order */
            $order = $this->orderRepository->search($criteria, $context)
                ->first();
            if (!empty($order) && ($this->paymentService->isOrderPaid($order) || $this->paymentService->isCancelPaid(
                $order
            ))) {
                $result = [
                    'reload' => true,
                ];
            } elseif (!empty($order)) {
                $twintOrder = $this->paymentService->checkOrderStatus($order);
                if (!$twintOrder instanceof Order) {
                    $result = [
                        'reload' => false,
                    ];
                } elseif ($twintOrder instanceof Order && $twintOrder->status()->equals(
                    OrderStatus::SUCCESS()
                ) || $twintOrder->status()
                    ->equals(OrderStatus::FAILURE())) {
                    $result = [
                        'reload' => true,
                    ];
                }
            }
        } catch (Exception $e) {
            return new JsonResponse([
                'reload' => false,
                'error' => $e->getMessage(),
            ]);
        }
        return new JsonResponse($result);
    }
}
