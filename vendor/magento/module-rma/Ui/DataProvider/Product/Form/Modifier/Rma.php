<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Rma\Model\Product\Source;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Field;

/**
 * Class for Product Form Modifier Rma
 */
class Rma extends AbstractModifier
{
    const FIELD_IS_RMA_ENABLED = 'is_returnable';

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
    }

    /**
     * Modify Rma Ui meta.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->getGroupCodeByField($meta, self::FIELD_IS_RMA_ENABLED)) {
            return $meta;
        }

        $containerPath = $this->arrayManager->findPath(
            static::CONTAINER_PREFIX . static::FIELD_IS_RMA_ENABLED,
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
            $containerPath . '/children/' . self::FIELD_IS_RMA_ENABLED . static::META_CONFIG_PATH,
            $meta,
            [
                'dataScope' => self::FIELD_IS_RMA_ENABLED,
                'imports' => [
                    'disabled' =>
                        '${$.parentName}.use_config_' . self::FIELD_IS_RMA_ENABLED . ':disableIsReturnable',
                    '__disableTmpl' => ['disabled' => false],
                ],
                'formElement' => Checkbox::NAME,
                'componentType' => Field::NAME,
                'prefer' => 'toggle',
                'additionalClasses' => 'admin__field-x-small',
                'templates' => [
                    'checkbox' => 'ui/form/components/single/switcher',
                ],
                'valueMap' => [
                    'false' => '0',
                    'true' => '1',
                ],
                'sortOrder' => 10,
            ]
        );
        $meta = $this->arrayManager->set(
            $containerPath . '/children/' . 'use_config_' . self::FIELD_IS_RMA_ENABLED . static::META_CONFIG_PATH,
            $meta,
            [
                'dataType' => 'number',
                'formElement' => Checkbox::NAME,
                'componentType' => Field::NAME,
                'component' => 'Magento_Rma/js/components/use-config-settings/single-checkbox',
                'description' => __('Use Config Settings'),
                'dataScope' => 'use_config_' . self::FIELD_IS_RMA_ENABLED,
                'valueUpdate' => true,
                'valueMap' => [
                    'false' => '0',
                    'true' => '1',
                ],
                'sortOrder' => 20,
                'imports' => [
                    'disabled' =>
                        '${$.parentName}.' . self::FIELD_IS_RMA_ENABLED . ':isUseDefault',
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

    /**
     * Modify Rma Ui data.
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $modelId = $this->locator->getProduct()->getId();
        $isUseDefaultChecked =
            !empty($data[$modelId][self::DATA_SOURCE_DEFAULT][self::FIELD_IS_RMA_ENABLED])
            && ($data[$modelId][self::DATA_SOURCE_DEFAULT][self::FIELD_IS_RMA_ENABLED] ==
                Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG);

        if ($isUseDefaultChecked || !$modelId) {
            $data[$modelId][self::DATA_SOURCE_DEFAULT]['use_config_' . self::FIELD_IS_RMA_ENABLED] = '1';
        }

        return $data;
    }
}
