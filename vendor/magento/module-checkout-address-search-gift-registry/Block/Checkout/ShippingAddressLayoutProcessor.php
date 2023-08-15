<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CheckoutAddressSearchGiftRegistry\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\CheckoutAddressSearch\Model\Config;
use Magento\Customer\Model\CustomerFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\GiftRegistry\Model\GiftRegistryConfigProvider;

/**
 * Processor for customer shipping address search on checkout page with gift registry.
 */
class ShippingAddressLayoutProcessor implements LayoutProcessorInterface
{
    /**
     * Address Search configuration.
     *
     * @var Config
     */
    private $addressConfig;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var GiftRegistryConfigProvider
     */
    private $giftRegistryConfigProvider;

    /**
     * @param Config $addressConfig
     * @param CustomerFactory $customerFactory
     * @param CheckoutSession $checkoutSession
     * @param GiftRegistryConfigProvider $giftRegistryConfigProvider
     */
    public function __construct(
        Config $addressConfig,
        CustomerFactory $customerFactory,
        CheckoutSession $checkoutSession,
        GiftRegistryConfigProvider $giftRegistryConfigProvider
    ) {
        $this->addressConfig = $addressConfig;
        $this->customerFactory = $customerFactory;
        $this->checkoutSession = $checkoutSession;
        $this->giftRegistryConfigProvider = $giftRegistryConfigProvider;
    }

    /**
     * @inheritdoc
     *
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout): array
    {
        $giftRegistryConfig = $this->giftRegistryConfigProvider->getConfig();
        if (!$giftRegistryConfig['giftRegistry']['available'] || !$giftRegistryConfig['giftRegistry']['id']) {
            return $jsLayout;
        }
        $isEnabledAddressSearch = $this->addressConfig->isEnabledAddressSearch();
        $addressSearchLimit = $this->addressConfig->getSearchLimit();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $customerId = (int)$quote->getCustomerId();
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerFactory->create()->load($customerId);
        $numberOfAddresses = count($customer->getAddresses());

        if (false === $isEnabledAddressSearch || $numberOfAddresses < $addressSearchLimit || $numberOfAddresses === 0) {
            return $jsLayout;
        }

        $addressListLayoutConfig = $jsLayout['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']['address-list'] ?? null;
        $addressSearchLayoutConfig = $this->getAddressSearchLayoutConfig();
        $addressListLayoutConfig = array_replace_recursive($addressListLayoutConfig, $addressSearchLayoutConfig);
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['address-list'] = $addressListLayoutConfig;

        $beforeFormLayoutConfig = $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['before-form'] ?? null;
        $recipientAddressLayoutConfig = $this->getRecipientAddressLayoutConfig();
        $beforeFormLayoutConfig = array_replace_recursive($beforeFormLayoutConfig, $recipientAddressLayoutConfig);
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['before-form'] = $beforeFormLayoutConfig;

        return $jsLayout;
    }

    /**
     * Returns shipping address search layout config.
     *
     * @return array
     */
    private function getAddressSearchLayoutConfig(): array
    {
        return [
            'children' => [
                'addressDefault' => [
                    'component' => 'Magento_CheckoutAddressSearchGiftRegistry/js/view/shipping-address/default'
                ],
                'selectShippingAddressModal' => [
                    'children' => [
                        'searchShippingAddress' => [
                            'component' =>
                                'Magento_CheckoutAddressSearch/js/view/shipping-address/ui-select'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns recipient address layout config.
     *
     * @return array
     */
    private function getRecipientAddressLayoutConfig(): array
    {
        return [
            'children' => [
                'recipientAddress' => [
                    'component' =>
                        'Magento_CheckoutAddressSearchGiftRegistry/js/view/shipping-address/recipientAddress',
                    'displayArea' => 'before-form'
                ]
            ]
        ];
    }
}
