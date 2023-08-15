<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Ui\DataProvider\Product\Form\Modifier\Plugin;

use Magento\PricePermissions\Observer\ObserverData;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav as EavModifier;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardType;
use Magento\GiftCard\Model\Giftcard\Amount as GiftCardAmount;

/**
 * Plugin for Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav
 */
class Eav
{
    const META_ATTRIBUTE_CONFIG_PATH = 'arguments/data/config';

    /**
     * @var ObserverData
     */
    private $observerData;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @param ObserverData $observerData
     * @param ArrayManager $arrayManager
     * @param LocatorInterface $locator
     */
    public function __construct(ObserverData $observerData, ArrayManager $arrayManager, LocatorInterface $locator)
    {
        $this->observerData = $observerData;
        $this->arrayManager = $arrayManager;
        $this->locator = $locator;
    }

    /**
     * Setup readonly state and visibility for product price and status fields
     *
     * @param EavModifier $subject
     * @param array $result
     * @param ProductAttributeInterface $attribute
     * @param string $groupCode
     * @param int $sortOrder
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetupAttributeMeta(
        EavModifier $subject,
        $result,
        ProductAttributeInterface $attribute,
        $groupCode,
        $sortOrder
    ) {
        if ($attribute->getAttributeCode() === ProductAttributeInterface::CODE_STATUS
            && !$this->observerData->isCanEditProductStatus()
        ) {
            return $this->addDisabledMetaConfig($result);
        }

        return $this->restrictPriceAccess($result, $attribute);
    }

    /**
     * Setup readonly state and visibility for product price field's container
     *
     * @param EavModifier $subject
     * @param array $result
     * @param ProductAttributeInterface $attribute
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetupAttributeContainerMeta(
        EavModifier $subject,
        $result,
        ProductAttributeInterface $attribute
    ) {
        return $this->restrictPriceAccess($result, $attribute);
    }

    /**
     * Setup proper value for readonly product price and status fields
     *
     * @param EavModifier $subject
     * @param mixed $result
     * @param ProductAttributeInterface $attribute
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function afterSetupAttributeData(
        EavModifier $subject,
        $result,
        ProductAttributeInterface $attribute
    ) {
        try {
            /** @var Product $product */
            $product = $this->locator->getProduct();
        } catch (NoSuchEntityException $e) {
            return $result;
        } catch (NotFoundException $e) {
            return $result;
        }

        if ($attribute->getAttributeCode() === ProductAttributeInterface::CODE_STATUS
            && !$this->observerData->isCanEditProductStatus()
            && $product->isObjectNew()
        ) {
            return ProductStatus::STATUS_DISABLED;
        }

        if ($this->isPrice($attribute)
            && !$this->observerData->isCanEditProductPrice()
            && $product->isObjectNew()
            && $product->getTypeId() !== ProductType::TYPE_BUNDLE
        ) {
            return $this->getDefaultPriceValue($attribute, $result);
        }

        return $result;
    }

    /**
     * Restrict price access
     *
     * @param array $attributeMeta
     * @param ProductAttributeInterface $attribute
     * @return array
     */
    private function restrictPriceAccess(array $attributeMeta, ProductAttributeInterface $attribute)
    {
        if (!$this->isPrice($attribute)) {
            return $attributeMeta;
        }

        if (!$this->observerData->isCanReadProductPrice()) {
            $attributeMeta = $this->addHiddenMetaConfig($attributeMeta);
        } elseif (!$this->observerData->isCanEditProductPrice()) {
            $attributeMeta = $this->addDisabledMetaConfig($attributeMeta);
        }

        return $attributeMeta;
    }

    /**
     * Check is price attribute
     *
     * @param ProductAttributeInterface $attribute
     * @return bool
     */
    private function isPrice(ProductAttributeInterface $attribute)
    {
        $priceCodes = [ProductAttributeInterface::CODE_TIER_PRICE, 'price_type', 'allow_open_amount'];

        return $attribute->getFrontendInput() === 'price' || in_array($attribute->getAttributeCode(), $priceCodes);
    }

    /**
     * Disable attribute
     *
     * @param array $attributeMeta
     * @return array
     */
    private function addDisabledMetaConfig(array $attributeMeta)
    {
        return $this->arrayManager->merge(
            static::META_ATTRIBUTE_CONFIG_PATH,
            $attributeMeta,
            [
                'disabled' => true,
                'validation' => ['required' => false],
                'required' => false,
            ]
        );
    }

    /**
     * Hide attribute
     *
     * @param array $attributeMeta
     * @return array
     */
    private function addHiddenMetaConfig(array $attributeMeta)
    {
        /** @var Product $product */
        $product = $this->locator->getProduct();

        if ($product->isObjectNew()) {
            return $this->arrayManager->merge(
                static::META_ATTRIBUTE_CONFIG_PATH,
                $attributeMeta,
                [
                    'visible' => false,
                    'validation' => ['required' => false],
                    'required' => false
                ]
            );
        }

        return [];
    }

    /**
     * Get default price value
     *
     * @param ProductAttributeInterface $attribute
     * @param mixed $attributeData
     * @return float|int|string
     */
    private function getDefaultPriceValue(ProductAttributeInterface $attribute, $attributeData)
    {
        /** @var Product $product */
        $product = $this->locator->getProduct();
        $defaultProductPriceString = $this->observerData->getDefaultProductPriceString();

        if ($product->getTypeId() !== GiftCardType::TYPE_GIFTCARD) {
            return $attributeData;
        }

        switch ($attribute->getAttributeCode()) {
            case GiftCardAmount::KEY_WEBSITE_ID:
                return $this->locator->getStore()->getWebsiteId();
            break;
            case GiftCardAmount::KEY_VALUE:
            case GiftCardAmount::KEY_WEBSITE_VALUE:
                return (float)$defaultProductPriceString;
            break;
        }

        return $attributeData;
    }
}
