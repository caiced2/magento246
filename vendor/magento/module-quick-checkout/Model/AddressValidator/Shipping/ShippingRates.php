<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\AddressValidator\Shipping;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\QuickCheckout\Api\Data\AddressInterface;
use Magento\QuickCheckout\Model\AddressValidatorInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Validate basic address fields
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ShippingRates implements AddressValidatorInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @param Session $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Session $checkoutSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Validate the address
     *
     * @param AddressInterface $address
     * @return bool
     */
    public function validate(AddressInterface $address): bool
    {
        try {
            return $this->canRetrieveShippingRates(
                $this->quoteRepository->get($this->checkoutSession->getQuoteId()),
                $address
            );
        // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
        } catch (NoSuchEntityException $e) {
        }
        return false;
    }

    /**
     * Check if can retrieve shipping rates for an address
     *
     * @param CartInterface $quote
     * @param AddressInterface $address
     * @return bool
     */
    private function canRetrieveShippingRates(CartInterface $quote, AddressInterface $address): bool
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData(
            [
                'region' => $address->getRegion(),
                'region_id' => $address->getRegionId(),
                'region_code' => $address->getRegionCode(),
                'country_id' => $address->getCountryId(),
                'street' => $address->getStreet(),
                'email' => $address->getEmail(),
                'telephone' => $address->getTelephone(),
                'postcode' => $address->getPostcode(),
                'city' => $address->getCity(),
                'firstname' => $address->getFirstName(),
                'lastname' => $address->getLastName(),
                'company' => $address->getCompany(),
            ]
        );
        $rates = $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->getAllShippingRates();
        $result = !empty($rates);
        $shippingAddress->unsetData()
            ->removeAllShippingRates();
        return $result;
    }
}
