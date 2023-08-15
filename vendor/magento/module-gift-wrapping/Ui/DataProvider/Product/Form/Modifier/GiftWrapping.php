<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\GiftWrapping\Helper\Data;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Field;
use Magento\Catalog\Model\Product\Attribute\Source\Boolean;

/**
 * Class for Product Form Modifier GiftWrapping
 */
class GiftWrapping extends AbstractModifier
{
    const FIELD_GIFT_WRAPPING_AVAILABLE = 'gift_wrapping_available';
    const FIELD_GIFT_WRAPPING_PRICE = 'gift_wrapping_price';

    /**
     * Gift wrapping data
     *
     * @var Data
     */
    private $giftWrappingData;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @param LocatorInterface $locator
     * @param Data $data
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        LocatorInterface $locator,
        Data $data,
        ArrayManager $arrayManager
    ) {
        $this->locator = $locator;
        $this->giftWrappingData = $data;
        $this->arrayManager = $arrayManager;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        $modelId = $this->locator->getProduct()->getId();
        $useConfigValue = Boolean::VALUE_USE_CONFIG;

        $isConfigUsed = isset($data[$modelId][static::DATA_SOURCE_DEFAULT][static::FIELD_GIFT_WRAPPING_AVAILABLE])
            && $data[$modelId][static::DATA_SOURCE_DEFAULT][static::FIELD_GIFT_WRAPPING_AVAILABLE] == $useConfigValue;

        if ($isConfigUsed || empty($modelId)) {
            $data[$modelId][static::DATA_SOURCE_DEFAULT][static::FIELD_GIFT_WRAPPING_AVAILABLE] =
                $this->giftWrappingData->isGiftWrappingAvailableForItems();
            $data[$modelId][static::DATA_SOURCE_DEFAULT]['use_config_' . static::FIELD_GIFT_WRAPPING_AVAILABLE] =
                '1';
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->customizeAllowGiftWrappingField($meta);
        $meta = $this->customizePriceField($meta);

        return $meta;
    }

    /**
     * Customize field
     *
     * @param array $meta
     * @return array
     */
    private function customizePriceField(array $meta)
    {
        if (!$this->getGroupCodeByField($meta, static::FIELD_GIFT_WRAPPING_PRICE)) {
            return $meta;
        }

        $containerPath = $this->arrayManager->findPath(static::FIELD_GIFT_WRAPPING_PRICE, $meta, null, 'children');

        return $this->arrayManager->merge(
            $containerPath . static::META_CONFIG_PATH,
            $meta,
            [
                'addbefore' => $this->locator->getStore()->getBaseCurrency()->getCurrencySymbol(),
                'additionalClasses' => 'admin__field-small',
                'validate-number' => true,
            ]
        );
    }

    /**
     * Customization of allow gift wrapping field
     *
     * @param array $meta
     * @return array
     */
    private function customizeAllowGiftWrappingField(array $meta)
    {
        if (!$this->getGroupCodeByField($meta, static::CONTAINER_PREFIX . static::FIELD_GIFT_WRAPPING_AVAILABLE)) {
            return $meta;
        }

        $containerPath = $this->arrayManager->findPath(
            static::CONTAINER_PREFIX . static::FIELD_GIFT_WRAPPING_AVAILABLE,
            $meta
        );

        $meta = $this->arrayManager->merge(
            $containerPath . static::META_CONFIG_PATH,
            $meta,
            [
                'formElement' => 'container',
                'componentType' => 'container',
                'component' => 'Magento_Ui/js/form/components/group',
                'label' => false,
                'required' => false,
                'breakLine' => false,
            ]
        );
        $meta = $this->arrayManager->merge(
            $containerPath . '/children/' . static::FIELD_GIFT_WRAPPING_AVAILABLE . static::META_CONFIG_PATH,
            $meta,
            [
                'dataScope' => static::FIELD_GIFT_WRAPPING_AVAILABLE,
                'component' => 'Magento_Ui/js/form/element/single-checkbox-use-config',
                'componentType' => Field::NAME,
                'prefer' => 'toggle',
                'additionalClasses' => 'admin__field-x-small',
                'valueMap' => [
                    'false' => '0',
                    'true' => '1',
                ],
                'sortOrder' => 10,
            ]
        );
        $meta = $this->arrayManager->set(
            $containerPath . '/children/use_config_' . static::FIELD_GIFT_WRAPPING_AVAILABLE . static::META_CONFIG_PATH,
            $meta,
            [
                'dataType' => 'number',
                'formElement' => Checkbox::NAME,
                'componentType' => Field::NAME,
                'description' => __('Use Config Settings'),
                'dataScope' => 'use_config_' . static::FIELD_GIFT_WRAPPING_AVAILABLE,
                'valueMap' => [
                    'false' => '0',
                    'true' => '1',
                ],
                'exports' => [
                    'checked' => '${$.parentName}.' . static::FIELD_GIFT_WRAPPING_AVAILABLE . ':isUseConfig',
                    '__disableTmpl' => ['checked' => false],
                ],
                'sortOrder' => 20,
                'imports' => [
                    'disabled' => '${$.parentName}.' . static::FIELD_GIFT_WRAPPING_AVAILABLE . ':isUseDefault',
                    '__disableTmpl' => ['disabled' => false],
                ],
            ]
        );
        $meta = $this->arrayManager->merge(
            $containerPath,
            $meta,
            [
                'sortOrder' => $this->arrayManager->get(
                    $containerPath . static::META_CONFIG_PATH . '/sortOrder',
                    $meta
                ),
                'dataScope' => '',
            ]
        );

        return $meta;
    }
}
