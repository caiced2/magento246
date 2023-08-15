<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\UrlInterface;
use Magento\CheckoutAddressSearch\Model\Config;
use Magento\Customer\Model\CustomerFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Processor for customer shipping address on checkout page.
 */
class ShippingAddressProcessor implements LayoutProcessorInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

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
     * @var CustomerAddressProcessor
     */
    private $customerAddressProcessor;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param UrlInterface $urlBuilder
     * @param Config $addressConfig
     * @param CustomerFactory $customerFactory
     * @param CustomerAddressProcessor $customerAddressProcessor
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Config $addressConfig,
        CustomerFactory $customerFactory,
        CustomerAddressProcessor $customerAddressProcessor,
        CheckoutSession $checkoutSession
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->addressConfig = $addressConfig;
        $this->customerFactory = $customerFactory;
        $this->customerAddressProcessor = $customerAddressProcessor;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     *
     * @param array $jsLayout
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function process($jsLayout): array
    {
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

        $shippingAddressJsLayout = $jsLayout['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']['address-list'] ?? null;
        $shippingAddressOptions = $this->customerAddressProcessor->getFormattedOptions($quote);
        $config = $this->getLayoutConfig($shippingAddressOptions, $customerId, $numberOfAddresses);
        $mergedArray = array_replace_recursive($shippingAddressJsLayout, $config);
        $shippingAddressJsLayout = $mergedArray;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['address-list'] = $shippingAddressJsLayout;

        return $jsLayout;
    }

    /**
     * Returns layout config.
     *
     * @param array $addressOptions
     * @param int $customerId
     * @param int $numberOfAddresses
     * @return array
     */
    private function getLayoutConfig(array $addressOptions, int $customerId, int $numberOfAddresses): array
    {
        return [
            'component' => 'uiCollection',
            'template' => 'ui/collection',
            'children' => [
                'addressDefault' => [
                    'component' => 'Magento_CheckoutAddressSearch/js/view/shipping-address/selected',
                    'selectShippingAddressProvider' => '${ $.parentName }.selectShippingAddressModal',
                    '__disableTmpl' => ['selectShippingAddressProvider' => false],
                ],
                'selectShippingAddressModal' => [
                    'component' => 'Magento_Ui/js/modal/modal-component',
                    'options' => [
                        'title' => 'Select Shipping Address',
                        'modalClass' => 'shipping-address-modal modal-slide',
                    ],
                    'children' => [
                        'searchShippingAddress' => [
                            'label' => __('Select shipping address'),
                            'component' => 'Magento_CheckoutAddressSearch/js/view/shipping-address/ui-select',
                            'template' => 'Magento_CheckoutAddressSearch/ui-select',
                            'disableLabel' => true,
                            'filterOptions' => true,
                            'searchOptions' => true,
                            'chipsEnabled' => true,
                            'levelsVisibility' => '1',
                            'sortOrder' => 10,
                            'multiple' => false,
                            'closeBtn' => true,
                            'filterRateLimitMethod' => 'notifyWhenChangesStop',
                            'filterPlaceholder' => __('Search for city, state, street or zip'),
                            'missingValuePlaceholder' => __('Shipping Address with ID: %s doesn\'t exist'),
                            'isDisplayMissingValuePlaceholder' => true,
                            'isDisplayEmptyPlaceholder' => true,
                            'isRemoveSelectedIcon' => true,
                            'selectedPlaceholders' => [
                                'defaultPlaceholder' => ''
                            ],
                            'searchUrl' => $this->urlBuilder->getUrl(
                                'checkout_customer/address/search',
                                ['id' => $customerId]
                            ),
                            'options' => $addressOptions,
                            'total' => $numberOfAddresses,
                        ]
                    ]
                ]
            ]
        ];
    }
}
