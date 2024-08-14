<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service\Monitoring;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGenerator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\ExpressCheckout\Util\PaymentMethodUtil;
use Twint\Sdk\Value\Address;
use Twint\Sdk\Value\CustomerData;

class CustomerRegisterService
{
    public function __construct(
        private readonly NumberRangeValueGenerator $numberRangeValueGenerator,
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $addressRepository,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $countryRepository
    ) {
    }

    /**
     * @throws Exception
     */
    public function register(PairingEntity $pairing, SalesChannelContext $context): array
    {
        $customerData = $this->generateCustomerData($pairing, $context);

        $customer = $pairing->getCustomer();

        if ($customer instanceof CustomerEntity) {
            $address = $this->getMatchedAddress($customerData, $customer);

            if ($address instanceof CustomerAddressEntity) {
                return [$customer, $address->getId()];
            }

            $addressId = $this->createAddress($customer, $customerData['addresses'][0], $context);
            return [$customer, $addressId];
        }

        return $this->createNew($customerData, $context);
    }

    private function createAddress(CustomerEntity $customer, array $addressData, SalesChannelContext $context): string
    {
        $addressData['customerId'] = $customer->getId();
        $addressData['salutationId'] = $customer->getSalutationId();

        $this->addressRepository->create([$addressData], $context->getContext());

        return $addressData['id'];
    }

    private function getMatchedAddress(array $customerData, CustomerEntity $customer): ?CustomerAddressEntity
    {
        $tAddress = $customerData['addresses'][0];
        $tCombined = implode(
            '|',
            [$tAddress['street'],
                $tAddress['city'],
                $tAddress['zipcode'],
                $tAddress['countryId'],
                $tAddress['firstName'],
                $tAddress['lastName'],
            ]
        );

        /** @var CustomerAddressEntity $address */
        foreach ($customer->getAddresses() ?? [] as $address) {
            $combined = implode('|', [
                $address->getStreet(),
                $address->getCity(),
                $address->getZipcode(),
                $address->getCountryId(),
                $address->getFirstName(),
                $address->getLastName(),
            ]);

            if ($tCombined === $combined) {
                return $address;
            }
        }

        return null;
    }

    private function createNew(array $customerData, SalesChannelContext $context): array
    {
        $this->customerRepository->create([$customerData], $context->getContext());

        return [$this->customerRepository->search(new Criteria([$customerData['id']]), $context->getContext())
            ->first(), $customerData['defaultShippingAddressId']];
    }

    /**
     * @throws Exception
     */
    public function generateCustomerData(PairingEntity $pairing, SalesChannelContext $context): array
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
            'city' => $address->city(),
            'zipcode' => $address->zip(),
            'countryId' => (string) $this->getCountryId($context, $address->country()->__toString()),
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
            'firstName' => $address->firstName(),
            'lastName' => $address->lastName(),
            'email' => (string) $customerData->email(),
            'active' => true,
            'firstLogin' => new DateTimeImmutable(),
            'password' => $this->generateRandomPassword(),
            'id' => Uuid::randomHex(),
            'addresses' => [$swAddress],
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'guest' => true,
        ];
    }

    protected function generateRandomPassword(int $length = 15): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomPassword = '';

        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomPassword;
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
