<?php

declare(strict_types=1);

namespace Twint\Reporting\Subscriber;

use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\Core\Handler\TwintExpressPaymentHandler;
use Twint\Core\Handler\TwintRegularPaymentHandler;
use Twint\Core\Service\SettingServiceInterface;
use Twint\Core\Setting\Settings;
use Twint\Sdk\Value\InstallSource;
use function in_array;
use function round;

/**
 * @internal
 */
#[Package('checkout')]
class OrderTransactionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $transactionReportRepository,
        private readonly SettingServiceInterface $settingService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_transaction.state.paid' => 'onPaidStateTransition',
        ];
    }

    public function onPaidStateTransition(OrderStateMachineStateChangeEvent $event): void
    {
        $transaction = $event->getOrder()
            ->getTransactions()?->first();
        $handlerId = $transaction?->getPaymentMethod()?->getHandlerIdentifier();
        $setting = $this->settingService->getSetting($event->getSalesChannelId());
        $isTestMode = $setting->isTestMode();

        if (!isset($transaction, $handlerId) || $isTestMode || Settings::INSTALL_SOURCE !== InstallSource::STORE || $event->getContext()->getVersionId() !== Defaults::LIVE_VERSION || !in_array(
            $handlerId,
            [TwintExpressPaymentHandler::class, TwintRegularPaymentHandler::class],
            true
        )) {
            return;
        }

        $this->transactionReportRepository->upsert([[
            'orderTransactionId' => $transaction->getId(),
            'currencyIso' => $event->getOrder()
                ->getCurrency()?->getIsoCode(),
            'totalPrice' => round($transaction->getAmount()->getTotalPrice(), 2),
        ]], $event->getContext());
    }
}
