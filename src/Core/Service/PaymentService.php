<?php

namespace Twint\Core\Service;

use Exception;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
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
use Twint\Core\Setting\Settings;
use Twint\Core\Util\CryptoHandler;
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
use Twint\Util\Method\RegularPaymentMethod;
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
     * @var CryptoHandler
     */
    private CryptoHandler $cryptoService;

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
     * @param CryptoHandler $cryptoService
     */
    public function __construct(
        EntityRepository             $orderRepository,
        EntityRepository             $stateMachineRepository,
        EntityRepository             $stateMachineStateRepository,
        SettingService               $settingService,
        OrderTransactionStateHandler $transactionStateHandler,
        CryptoHandler                $cryptoService
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
        if(!$order instanceof OrderEntity || $order->getCurrency() == null){
            throw PaymentException::asyncProcessInterrupted($transaction->getOrderTransaction()->getId(),'Missing order or currency' . PHP_EOL);
        }
        $currency = $order->getCurrency()->getIsoCode();
        $client = $this->getApiClient($order->getSalesChannelId(), $transaction->getOrderTransaction()->getId());
        try {
            /**var non-empty-string $orderNumber**/
            $orderNumber = !empty($order->getOrderNumber()) ? $order->getOrderNumber() : $order->getId();
            /** @var Order **/
            return $client->startOrder(new UnfiledMerchantTransactionReference($orderNumber), new Money($currency, $order->getAmountTotal()));
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
    }

    /**
     * @param $order
     * @return Order|null
     */
    public function checkOrderStatus($order):?Order {
        try {
            $currency = $order->getCurrency()->getIsoCode();
            $orderTransactionId = $order->getTransactions()->first()->getId() ?? $order->getId();
            $twintApiResponse = json_decode($order->getCustomFields()[OrderCustomFieldInstaller::TWINT_API_RESPONSE_CUSTOM_FIELD] ?? '{}', true);
            if(empty($twintApiResponse) || empty($twintApiResponse['id'])){
                throw PaymentException::asyncProcessInterrupted($orderTransactionId, 'Missing Twint response for this order:'. $order->getId() . PHP_EOL);
            }
            $client = $this->getApiClient($order->getSalesChannelId(), $orderTransactionId);
            /** @var Order * */
            $twintOrder = $client->monitorOrder(new OrderId(new Uuid($twintApiResponse['id'])));
            if($twintOrder->status()->equals(OrderStatus::SUCCESS())){
                $this->transactionStateHandler->paid($order->getTransactions()->first()->getId(), $this->context);
                return $client->confirmOrder(new OrderId(new Uuid($twintApiResponse['id'])), new Money($currency, $order->getAmountTotal()));
            }
            else if($twintOrder->status()->equals(OrderStatus::FAILURE())){
                $this->transactionStateHandler->cancel($order->getTransactions()->first()->getId(), $this->context);
            }
            return $twintOrder;
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
    }

    /**
     * @param string $orderId
     * @param array $customFields
     */
    public function updateOrderCustomField(string $orderId, array $customFields):void {
        $this->orderRepository->update([[
            'id' => $orderId,
            'customFields' => $customFields
        ]], $this->context);
    }

    /**
     * @return EntityCollection
     */
    public function getPendingOrders(): EntityCollection
    {
        $onlyPickOrderFromMinutes = Settings::ONLY_PICK_ORDERS_FROM_MINUTES;
        $paymentInProgressStateId = $this->getPaymentInProgressStateId();
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('transactions.stateId', $paymentInProgressStateId)
        );
        $criteria->addFilter(new EqualsAnyFilter('order.transactions.paymentMethod.technicalName', [RegularPaymentMethod::TECHNICAL_NAME]));
        /** @var int $time */
        $time = strtotime("-$onlyPickOrderFromMinutes minutes");
        $time = date("Y-m-d H:i:s", $time);
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new RangeFilter('createdAt', [RangeFilter::GT => $time]),
                    new RangeFilter('updatedAt', [RangeFilter::GT => $time])
                ]
            )

        );
        $criteria->addAssociation('currency');
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addAssociation('customFields');
        return $this->orderRepository->search($criteria, $this->context)->getEntities();
    }

    /**
     * @return string
     */
    private function getPaymentInProgressStateId(): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE));
        $transactionState = $this->stateMachineRepository->search($criteria, $this->context)->first();
        if (!empty($transactionState)) {
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
     * @param string $salesChannelId
     * @param string $orderTransactionId
     * @return Client
     * @throw PaymentException
     */
    public function getApiClient(string $salesChannelId, string $orderTransactionId): Client
    {
        $setting = $this->settingService->getSetting($salesChannelId);
        $merchantId = $setting->getMerchantId();
        $certificate = $setting->getCertificate();
        $environment = $setting->isTestMode() ? Environment::TESTING() : Environment::PRODUCTION();
        if (empty($merchantId)) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'Missing merchantId for config' . PHP_EOL
            );
        }
        if (empty($certificate)) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'Missing certificate:'  . PHP_EOL
            );
        }
        try {
            $pemContent = $this->cryptoService->decrypt($certificate['cert'] ?? '') . $this->cryptoService->decrypt($certificate['pkey'] ?? '');
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
            if($status->isOk()){
                return $client;
            }
            else{
                throw PaymentException::asyncProcessInterrupted($orderTransactionId, 'API is not available');
            }

        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted($orderTransactionId, $e->getMessage());
        }
    }
    /**
     * @param OrderEntity $order
     * @return bool
     */
    public function isOrderPaid(OrderEntity $order): bool
    {
        $transactions = $order->getTransactions();

        if ($transactions === null) {
            return false;
        }

        $transaction = $transactions->last();
        if ($transaction === null) {
            return false;
        }

        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            return false;
        }
        return $stateMachineState->getTechnicalName() === OrderTransactionStates::STATE_PAID;
    }
    /**
     * @param OrderEntity $order
     * @return bool
     */
    public function isCancelPaid(OrderEntity $order): bool
    {
        $transactions = $order->getTransactions();

        if ($transactions === null) {
            return false;
        }

        $transaction = $transactions->last();
        if ($transaction === null) {
            return false;
        }

        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            return false;
        }
        return $stateMachineState->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED;
    }
}
