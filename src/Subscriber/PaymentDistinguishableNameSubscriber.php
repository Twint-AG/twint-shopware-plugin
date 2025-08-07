<?php

declare(strict_types=1);

namespace Twint\Subscriber;

use Shopware\Core\Checkout\Payment\PaymentEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentDistinguishableNameSubscriber implements EventSubscriberInterface
{
    private const TWINT_HANDLERS = [
        'Twint\Core\Handler\TwintRegularPaymentHandler',
        'Twint\Core\Handler\TwintExpressPaymentHandler',
    ];

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
            if (!in_array($payment->getHandlerIdentifier(), self::TWINT_HANDLERS, true)) {
                continue;
            }
            $translatedName = $this->translator->trans(
                'twintPayment.administration.name.' . $payment->getTechnicalName()
            );
            $payment->addTranslated('distinguishableName', $translatedName);
            $payment->setDistinguishableName($translatedName);
        }
    }
}
