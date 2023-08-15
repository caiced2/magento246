<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Form\AttributeMapper;
use Magento\Checkout\Block\Checkout\AttributeMerger as AttributeMergerBlock;

/**
 * Layout processor for checkout with customer address search.
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @var AttributeMetadataDataProvider
     */
    protected $attributeMetadataDataProvider;

    /**
     * @var AttributeMapper
     */
    protected $attributeMapper;

    /**
     * @var AttributeMergerBlock
     */
    protected $merger;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param AttributeMapper $attributeMapper
     * @param AttributeMergerBlock $merger
     * @param ScopeConfigInterface|null $scopeConfig
     */
    public function __construct(
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        AttributeMapper $attributeMapper,
        AttributeMergerBlock $merger,
        ScopeConfigInterface $scopeConfig = null
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->scopeConfig = $scopeConfig ?: ObjectManager::getInstance()->get(ScopeConfigInterface::class);
    }

    /**
     * Process js Layout of block
     *
     * @param array $jsLayout
     * @return array
     * @throws LocalizedException
     */
    public function process($jsLayout)
    {
        $enableAddressSearchConfig = (bool)$this->scopeConfig->getValue(
            'checkout/options/enable_address_search',
            ScopeInterface::SCOPE_STORE
        );

        // do not proceed if billing address is managed with ui-select
        if ($enableAddressSearchConfig &&
            empty($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['payments-list'])
        ) {
            return $jsLayout;
        }

        $addressCustomAttributes = $this->getAddressCustomAttributes();

        $jsLayout = $this->processCustomAttributesForPaymentMethods($jsLayout, $addressCustomAttributes);
        $jsLayout = $this->mergeCustomAttributes($jsLayout, $addressCustomAttributes);
        $jsLayout = $this->mergeCustomAttributesOfBillingAddress($jsLayout, $addressCustomAttributes);

        return $jsLayout;
    }

    /**
     * Returns a list of custom attributes for customer addresses.
     *
     * @return array
     * @throws LocalizedException
     */
    private function getAddressCustomAttributes()
    {
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer_address',
            'customer_register_address'
        );
        $addressCustomAttributes = [];
        foreach ($attributes as $attribute) {
            if (!$attribute->getIsUserDefined()) {
                continue;
            }
            $addressCustomAttributes[$attribute->getAttributeCode()] = $this->attributeMapper->map($attribute);
        }

        return $addressCustomAttributes;
    }

    /**
     * Render shipping address for payment methods.
     *
     * @param array $jsLayout
     * @param array $addressCustomAttributes
     * @return array
     */
    private function processCustomAttributesForPaymentMethods(
        array $jsLayout,
        array $addressCustomAttributes
    ): array {
        // The following code is a workaround for custom address attributes
        $paymentMethodRenders = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['payments-list']['children'];
        if (\is_array($paymentMethodRenders)) {
            foreach ($paymentMethodRenders as $name => $renderer) {
                if (isset($renderer['children']) && array_key_exists('form-fields', $renderer['children'])) {
                    $fields = $renderer['children']['form-fields']['children'];
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
                    ['children']['payment']['children']['payments-list']['children'][$name]['children']
                    ['form-fields']['children'] = $this->merger->merge(
                        $addressCustomAttributes,
                        'checkoutProvider',
                        $renderer['dataScopePrefix'] . '.custom_attributes',
                        $fields
                    );
                }
            }
        }

        return $jsLayout;
    }

    /**
     * Merge custom attributes of shipping address.
     *
     * @param array $jsLayout
     * @param array $addressCustomAttributes
     * @return array
     */
    private function mergeCustomAttributes(
        array $jsLayout,
        array $addressCustomAttributes
    ): array {
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'])) {
            $fields = $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'] = $this->merger->merge(
                $addressCustomAttributes,
                'checkoutProvider',
                'shippingAddress.custom_attributes',
                $fields
            );
        }

        return $jsLayout;
    }

    /**
     * Merge custom attributes of billing address.
     *
     * @param array $jsLayout
     * @param array $addressCustomAttributes
     * @return array
     */
    private function mergeCustomAttributesOfBillingAddress(
        array $jsLayout,
        array $addressCustomAttributes
    ): array {
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['afterMethods']['children']['billing-address-form']
            ['children']['form-fields'])) {
            $fields = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['afterMethods']['children']['billing-address-form']
            ['children']['form-fields']['children'];
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['afterMethods']['children']['billing-address-form']
            ['children']['form-fields']['children'] = $this->merger->merge(
                $addressCustomAttributes,
                'checkoutProvider',
                'billingAddressshared.custom_attributes',
                $fields
            );
        }

        return $jsLayout;
    }
}
