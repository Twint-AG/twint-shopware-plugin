<?php

declare(strict_types=1);

namespace Twint\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\ApiException;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twint\Core\Service\PaymentService;
use Twint\Sdk\Value\Order;

class CancelTwintOrderSubscriber implements EventSubscriberInterface
{
    private string $shopwareVersion;

    private PaymentService $paymentService;

    private LoggerInterface $logger;

    public function __construct(PaymentService $paymentService, LoggerInterface $loggerService, string $shopwareVersion)
    {
        $this->paymentService = $paymentService;
        $this->shopwareVersion = $shopwareVersion;
        $this->logger = $loggerService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'state_machine.order.state_changed' => ['onOrderStateChanges'],
        ];
    }

    public function onOrderStateChanges(StateMachineStateChangeEvent $event): void
    {
        if ($event->getTransitionSide() !== StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER) {
            return;
        }

        $allowedStates = [
            StateMachineTransitionActions::ACTION_CANCEL => true,
        ];

        if (version_compare($this->shopwareVersion, '6.2', '>=')) {
            $allowedStates[StateMachineTransitionActions::ACTION_FAIL] = true;
        }

        $transitionName = $event->getTransition()
            ->getTransitionName();

        if (!isset($allowedStates[$transitionName])) {
            return;
        }

        $order = $this->paymentService->getOrder($event->getTransition()->getEntityId(), $event->getContext());
        try {
            $twintOrder = $this->paymentService->reverseOrder($order);
            if ($twintOrder instanceof Order) {
                $this->logger->info(
                    sprintf('TWINT order "%s" is reversed successfully!', $order->getOrderNumber())
                );
            }
        } catch (ApiException $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
