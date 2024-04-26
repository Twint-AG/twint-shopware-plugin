<?php declare(strict_types=1);

namespace Twint\Storefront\Controller;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twint\Core\Service\CryptoService;
use Twint\Util\OrderCustomFieldInstaller;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PaymentController extends StorefrontController
{
    public function __construct(private $orderRepository, private CryptoService $cryptoService)
    {
        $this->orderRepository = $orderRepository;
        $this->cryptoService = $cryptoService;
    }
    #[Route(
        path: '/payment/waiting/{orderNumber}',
        name: 'frontend.twint.waiting',
        methods: ['GET']
    )]
    public function showWaiting(Request $request, SalesChannelContext $context): Response
    {
        $orderNumber = $request->get('orderNumber');
        $orderNumber = $this->cryptoService->unHash($orderNumber);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        $criteria->addAssociation('orderCustomer.customer')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('lineItems')
            ->addAssociation('currency')
            ->addAssociation('addresses.country')
            ->addAssociation('customFields');;
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context->getContext())->first();
        $twintApiResponse = json_decode($order->getCustomFields()[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] ?? '{}', true);
        $qrcode = '';
        if($twintApiResponse){
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
            'order' => $order
        ]);
    }
}
