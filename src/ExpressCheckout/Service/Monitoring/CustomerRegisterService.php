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
use Twint\Sdk\Value\Address;
use Twint\Sdk\Value\CustomerData;

class CustomerRegisterService
{
    public function __construct(
        private readonly NumberRangeValueGenerator $numberRangeValueGenerator,
        private readonly EntityRepository $customerRepository,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $countryRepository
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
        /** @var CustomerData $customerData */
        $customerData = $pairing->getCustomerData();

        /** @var Address $address */
        $address = $customerData->shippingAddress();

        $channel = $context->getSalesChannel();
        $id = Uuid::randomHex();
        $addressId = Uuid::randomHex();
        $swAddress = [
            'salutationId' => $this->getSalutationId($context),
            'street' => $address->street(),
            'city' => (string) $address->city(),
            'zipcode' => (string) $address->zip(),
            'countryId' => (string) $this->getCountryId($context, $address->country() ->__toString()),
            'countryStateId' => null,
            'firstName' => $address->firstName(),
            'lastName' => $address->lastName(),
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
            'firstName' => (string) $address->firstName(),
            'lastName' => (string) $address->lastName(),
            'email' => (string) $customerData->email(),
            'active' => true,
            'firstLogin' => new DateTimeImmutable(),
            'password' => '12345678',
            'id' => Uuid::randomHex(),
            'addresses' => [$swAddress],
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

    private function getCountryId(SalesChannelContext $context, string $iso): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));

        return $this->countryRepository->searchIds($criteria, $context->getContext())
            ->firstId();
    }
}
