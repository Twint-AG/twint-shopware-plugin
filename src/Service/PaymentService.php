<?php

namespace Twint\Service;


use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Twint\Core\Service\CryptoService;
use Twint\Sdk\Certificate\CertificateContainer;
use Twint\Sdk\Certificate\PemCertificate;
use Twint\Sdk\Client;
use Twint\Sdk\Io\InMemoryStream;
use Twint\Sdk\Value\Environment;
use Twint\Sdk\Value\MerchantId;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;
use Twint\Util\Method\DefaultMethod;
use Twint\Util\OrderCustomFieldInstaller;

/**
 * Class PaymentService
 */
class PaymentService
{
    /**
     * @var EntityRepository
     */
    private EntityRepository $orderRepository;

    /**
     * @var EntityRepository
     */
    private EntityRepository $stateMachineRepository;

    /**
     * @var EntityRepository
     */
    private EntityRepository $stateMachineStateRepository;

    /**
     * @var SettingService
     */
    private SettingService $settingService;

    /**
     * @var OrderTransactionStateHandler
     */
    private OrderTransactionStateHandler $transactionStateHandler;

    /**
     * @var CryptoService
     */
    private CryptoService $cryptoService;

    /**
     * @var Context
     */
    private Context $context;

    /**
     * AccountService constructor.
     * @param EntityRepository $orderRepository
     * @param EntityRepository $stateMachineRepository
     * @param EntityRepository $stateMachineStateRepository
     * @param SettingService $settingService
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param CryptoService $cryptoService
     */
    public function __construct(
        EntityRepository $orderRepository,
        EntityRepository $stateMachineRepository,
        EntityRepository $stateMachineStateRepository,
        SettingService $settingService,
        OrderTransactionStateHandler $transactionStateHandler,
        CryptoService $cryptoService
    )
    {
        $this->orderRepository = $orderRepository;
        $this->stateMachineRepository = $stateMachineRepository;
        $this->stateMachineStateRepository = $stateMachineStateRepository;
        $this->settingService = $settingService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->cryptoService = $cryptoService;
        $this->context = new Context(new SystemSource());
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @return Order
     */
    public function createOrder(AsyncPaymentTransactionStruct $transaction): Order
    {
        $order = $transaction->getOrder();
        $currency = $order->getCurrency()->getIsoCode();
        $client = $this->getApiClient($order->getSalesChannelId());
        try {
            /** @var Order **/
            return $client->startOrder(new UnfiledMerchantTransactionReference($order->getOrderNumber()), new Money($currency, $order->getAmountTotal()));
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
    }

    public function checkOrderStatus($order):Order {
        try {
            $currency = $order->getCurrency()->getIsoCode();
            $twintApiResponse = json_decode($order->getCustomFields()[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] ?? '{}', true);
            if(empty($twintApiResponse) || empty($twintApiResponse['id'])){
                throw PaymentException::asyncProcessInterrupted(
                    $order->getId(),
                    'Missing Twint response for this order:'. $order->getId() . PHP_EOL
                );
            }
            $client = $this->getApiClient($order->getSalesChannelId());
            /** @var Order **/
            $twintOrder = $client->monitorOrder(new OrderId(new Uuid($twintApiResponse['id'])));
            if(true || $twintOrder->transactionStatus() == OrderStatus::SUCCESS){
                $this->transactionStateHandler->paid($order->getTransactions()->first()->getId(), $this->context);
                return $client->confirmOrder(new OrderId(new Uuid($twintApiResponse['id'])), new Money($currency, $order->getAmountTotal()));
            }
            return $twintOrder;
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $order->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
    }

    /**
     * @param $orderId
     * @param $customFields
     */
    public function updateOrderCustomField($orderId, $customFields):void {
        $this->orderRepository->update([[
            'id' => $orderId,
            'customFields' => $customFields
        ]], $this->context);
    }

    /**
     * @return EntityCollection
     */
    public function getPendingOrders():EntityCollection
    {
        $onlyPickOrderFromMinutes = $this->settingService->getSetting()->getOnlyPickOrderFromMinutes();
        $paymentInProgressStateId = $this->getPaymentInProgressStateId();
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('transactions.stateId', $paymentInProgressStateId)
        );
        $criteria->addFilter(new EqualsAnyFilter('order.transactions.paymentMethod.technicalName', [DefaultMethod::TECHNICAL_NAME]));
        if($onlyPickOrderFromMinutes > 0){
            $time = date("Y-m-d H:i:s", strtotime("-$onlyPickOrderFromMinutes minutes"));
            $criteria->addFilter(
                new MultiFilter(
                    MultiFilter::CONNECTION_OR,
                    [
                        new RangeFilter('createdAt', [RangeFilter::GT => $time]),
                        new RangeFilter('updatedAt', [RangeFilter::GT => $time])
                    ]
                )

            );
        }
        $criteria->addAssociation('currency');
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addAssociation('customFields');
        return $this->orderRepository->search($criteria, $this->context)->getEntities();
    }
    /**
     * @return string
     */
    private function getPaymentInProgressStateId():string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE));
        $transactionState = $this->stateMachineRepository->search($criteria, $this->context)->first();
        if(!empty($transactionState)) {
            $transactionStateId = $transactionState->get('id');
            $criteriaM = new Criteria();

            $criteriaM->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('technicalName', OrderTransactionStates::STATE_IN_PROGRESS),
                new EqualsFilter('stateMachineId', $transactionStateId)
            ]));
            $paymentInProgressState = $this->stateMachineStateRepository->search($criteriaM, $this->context)->first();
            if (!empty($paymentInProgressState)) {
                return $paymentInProgressState->get('id');
            }
        }
        return '';
    }

    /**
     * @param $salesChannelId
     * @return Client
     * @throw PaymentException
     */
    public function getApiClient($salesChannelId):Client {
        $setting = $this->settingService->getSetting($salesChannelId);
        $merchantId = $setting->getMerchantId();
        $certificate = $setting->getCertificate();
        $environment = $setting->isTestMode() ? Environment::TESTING() : Environment::PRODUCTION();
        if(empty($merchantId)){
            throw PaymentException::asyncProcessInterrupted(
                $salesChannelId,
                'Missing merchantId for config' . PHP_EOL
            );
        }
        if(empty($certificate)){
            throw PaymentException::asyncProcessInterrupted(
                $salesChannelId,
                'Missing certificate:'. $salesChannelId . PHP_EOL
            );
        }
        try{
            $pemContent = $this->cryptoService->decrypt($certificate['cert'] ?? '').$this->cryptoService->decrypt($certificate['pkey'] ?? '');
            $client = new Client(
                CertificateContainer::fromPem(
                    new PemCertificate(
                        new InMemoryStream($pemContent),
                        ''
                    )
                ),
                MerchantId::fromString($merchantId),
                Version::latest(),
                $environment,
            );
            $status = $client->checkSystemStatus();
            return $client;
        }
        catch(\Exception $e){
            throw PaymentException::asyncProcessInterrupted($salesChannelId, $e->getMessage());
        }
    }
}
