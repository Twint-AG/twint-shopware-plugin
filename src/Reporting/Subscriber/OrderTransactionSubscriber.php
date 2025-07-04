<?php

declare(strict_types=1);

namespace Twint\Reporting\Subscriber;

use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\Reporting\Service\TransactionReportService;

/**
 * @internal
 */
#[Package('checkout')]
class OrderTransactionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TransactionReportService $transactionReportService,
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
        $this->transactionReportService->processTransactionReport($event->getOrder(), $event->getContext());
    }
}
