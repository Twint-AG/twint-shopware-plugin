<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGenerator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\TwintPairingEntity;
use Twint\ExpressCheckout\Util\PaymentMethodUtil;

class CustomerRegisterService
{
    public function __construct(
        private readonly NumberRangeValueGenerator $numberRangeValueGenerator,
        private readonly EntityRepository $customerRepository,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly EntityRepository $salutationRepository
    ) {
    }

    public function register(TwintPairingEntity $pairing, SalesChannelContext $context): array
    {
        $customerData = $this->generateCustomerData($pairing, $context);
        $this->customerRepository->create([$customerData], $context->getContext());

        return [$this->customerRepository->search(new Criteria([$customerData['id']]), $context->getContext())
            ->first(), $customerData];
    }

    /**
     * @throws Exception
     */
    public function generateCustomerData(TwintPairingEntity $pairing, SalesChannelContext $context): array
    {
        $channel = $context->getSalesChannel();
        $id = Uuid::randomHex();
        $addressId = Uuid::randomHex();
        $address = [
            'salutationId' => $this->getSalutationId($context),
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
            'lastName' => 'User',
            'email' => Uuid::randomHex() . '@example.com',
            'active' => true,
            'firstLogin' => new DateTimeImmutable(),
            'password' => '12345678',
            'id' => Uuid::randomHex(),
            'addresses' => [$address],
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
        ];
    }

    private function getSalutationId(SalesChannelContext $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', 'not_specified'));

        return $this->salutationRepository->searchIds($criteria, $context->getContext())
            ->firstId();
    }
}
