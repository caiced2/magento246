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
 * Process billing address on checkout.
 */
class BillingAddressProcessor implements LayoutProcessorInterface
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
        $isDisplayBillingOnPaymentPageAvailable = $this->addressConfig->isDisplayBillingOnPaymentPageAvailable();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $customerId = (int)$quote->getCustomerId();
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerFactory->create()->load($customerId);
        $numberOfAddresses = count($customer->getAddresses());

        if (false === $isEnabledAddressSearch || $numberOfAddresses < $addressSearchLimit || $numberOfAddresses === 0) {
            return $jsLayout;
        }

        $paymentList = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'] ?? null;

        // display billing address search on payment method
        if (null !== $paymentList && !$isDisplayBillingOnPaymentPageAvailable) {
            $billingAddressOptions = $this->customerAddressProcessor->getFormattedOptions($quote);

            foreach ($paymentList as $paymentCode => $payment) {
                if (!isset($payment['children']['billingAddressList'])) {
                    continue;
                }
                $paymentMethod = $paymentList[$paymentCode];
                $addressSearchConfig = $this->getLayoutConfig(
                    $billingAddressOptions,
                    $customerId,
                    $numberOfAddresses,
                    $paymentCode
                );
                $paymentList[$paymentCode] = $this->updateConfig($paymentMethod, $addressSearchConfig);
            }
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'] = $paymentList;
        }

        $jsLayout = $this->prepareBillingAddressForm(
            $quote,
            $jsLayout,
            $isDisplayBillingOnPaymentPageAvailable,
            $numberOfAddresses
        );

        return $jsLayout;
    }

    /**
     * Prepare data for billing address form.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $jsLayout
     * @param bool $isDisplayBillingOnPaymentPageAvailable
     * @param int $numberOfAddresses
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareBillingAddressForm(
        \Magento\Quote\Model\Quote $quote,
        array $jsLayout,
        bool $isDisplayBillingOnPaymentPageAvailable,
        int $numberOfAddresses
    ) : array {
        $billingAddressForm
            = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['afterMethods']['children']['billing-address-form'] ?? null;

        // display billing address search on payment page
        if (null !== $billingAddressForm && $isDisplayBillingOnPaymentPageAvailable) {
            $customerId = (int) $quote->getCustomerId();
            $billingAddressOptions = $this->customerAddressProcessor->getFormattedOptions($quote);
            $addressSearchConfig = $this->getLayoutConfig(
                $billingAddressOptions,
                $customerId,
                $numberOfAddresses,
                'billing-address-form'
            );
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['afterMethods']['children']['billing-address-form'] = $this->updateConfig(
                $billingAddressForm,
                $addressSearchConfig
            );
        }

        return $jsLayout;
    }

    /**
     * Update component configuration.
     *
     * @param array $component
     * @param array $addressSearchConfig
     * @return array
     */
    public function updateConfig(array $component, array $addressSearchConfig): array
    {
        $component['component'] = 'Magento_CheckoutAddressSearch/js/view/billing-address';
        $component['selectBillingAddressProvider'] =
            '${$.name}.billingAddressList.selectBillingAddressModal';
        $component['__disableTmpl'] = ['selectBillingAddressProvider' => false];
        $billingAddressList = $component['children']['billingAddressList'];
        $mergedArray = array_replace_recursive($billingAddressList, $addressSearchConfig);
        $component['children']['billingAddressList'] = $mergedArray;

        return $component;
    }

    /**
     * Returns billing address search layout config.
     *
     * @param array $addressOptions
     * @param int $customerId
     * @param int $numberOfAddresses
     * @param string|null $paymentCode
     * @return array
     */
    private function getLayoutConfig(
        array $addressOptions,
        int $customerId,
        int $numberOfAddresses,
        ?string $paymentCode = null
    ): array {
        return [
            'component' => 'uiCollection',
            'template' => 'ui/collection',
            'children' => [
                'newBillingAddressButton' => [
                    'component' => 'Magento_Ui/js/form/components/button',
                    'title' => 'New Address',
                    'buttonClasses' => 'new-billing-address-button',
                    'displayAsLink' => false,
                    'imports' => [
                        'visible' => '!index = ' . $paymentCode . ':isNewAddressAdded'
                    ],
                    'config' => [
                        'actions' => [
                            '0' => [
                                'targetName' => 'index = ' . $paymentCode,
                                'actionName' => 'showFormPopUp'
                            ]
                        ]
                    ]
                ],
                'selectBillingAddressModal' => [
                    'component' => 'Magento_CheckoutAddressSearch/js/modal-component',
                    'options' => [
                        'title' => 'Select Billing Address',
                        'modalClass' => 'billing-address-modal modal-slide'
                    ],
                    'children' => [
                        'searchBillingAddress' => [
                            'label' => __('Select billing address'),
                            'component' => 'Magento_CheckoutAddressSearch/js/view/billing-address/ui-select',
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
                            'missingValuePlaceholder' => __('Billing Address with ID: %s doesn\'t exist'),
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
                            'billingAddressProvider' => 'index = ' . $paymentCode,
                            'total' => $numberOfAddresses,
                        ]
                    ]
                ]
            ]
        ];
    }
}
