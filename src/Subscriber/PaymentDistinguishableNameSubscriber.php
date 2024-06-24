<?php

declare(strict_types=1);

namespace Twint\Subscriber;

use Shopware\Core\Checkout\Payment\PaymentEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twint\Core\Handler\TwintExpressPaymentHandler;
use Twint\Core\Handler\TwintRegularPaymentHandler;

class PaymentDistinguishableNameSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEvents::PAYMENT_METHOD_LOADED_EVENT => ['changeDistinguishablePaymentName', 10000],
        ];
    }

    public function changeDistinguishablePaymentName(EntityLoadedEvent $event): void
    {
        /** @var PaymentMethodEntity $payment */
        foreach ($event->getEntities() as $payment) {
            if ($payment->getHandlerIdentifier() === TwintRegularPaymentHandler::class || $payment->getHandlerIdentifier() === TwintExpressPaymentHandler::class) {
                $payment->addTranslated(
                    'distinguishableName',
                    $this->translator->trans('twintPayment.administration.name.' . $payment->getTechnicalName())
                );
                $payment->setDistinguishableName(
                    $this->translator->trans('twintPayment.administration.name.' . $payment->getTechnicalName())
                );
            }
        }
    }
}
