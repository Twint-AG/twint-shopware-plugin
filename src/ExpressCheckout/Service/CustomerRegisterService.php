<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGenerator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\ExpressCheckout\Util\PaymentMethodUtil;

class CustomerRegisterService
{
    public function __construct(
        private readonly NumberRangeValueGenerator $numberRangeValueGenerator,
        private readonly EntityRepository $customerRepository,
        private readonly PaymentMethodUtil $paymentMethodUtil,
    ) {
    }

//    public function register(SalesChannelContext $context): Entity
//    {
//        $customerData = $this->generateCustomerData($context);
//        $customer = $this->customerRepository->create([$customerData], $context->getContext());
//
//        return $customer;
//    }

    /**
     * @throws Exception
     */
    public function generateCustomerData(SalesChannelContext $context): array
    {
        $channel = $context->getSalesChannel();
        $id = Uuid::randomHex();
        $addressId = Uuid::randomHex();
        $address = [
            'street' => 'Parallelweg 30',
            'city' => 'City',
            'zipcode' => '',
            'countryId' => '018f386c494770e39c6572daf808dc8a',
            'countryStateId' => null,
            'firstName' => 'Update',
            'lastName' => 'Later',
            'id' => $addressId,
            'customerId' => $id,
        ];

        return [
            'customerNumber' => $this->numberRangeValueGenerator->getValue(
                $this->customerRepository->getDefinition()
                    ->getEntityName(),
                $context->getContext(),
                $context->getSalesChannel()
                    ->getId()
            ),
            'salesChannelId' => $channel->getId(),
            'languageId' => $channel->getLanguageId(),
            'groupId' => $channel->getCustomerGroupId(),
            'defaultPaymentMethodId' => $this->paymentMethodUtil->getExpressCheckoutMethodId(),
            'firstName' => 'Express',
            'lastName' => 'Checkout',
            'email' => 'express@checkout.com',
            'active' => true,
            'firstLogin' => new DateTimeImmutable(),
            'password' => '12345678',
            'id' => Uuid::randomHex(),
            'addresses' => [$address],
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
        ];
    }
}
