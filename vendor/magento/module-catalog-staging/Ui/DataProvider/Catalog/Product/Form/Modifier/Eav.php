<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Ui\DataProvider\Catalog\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav as EavModifier;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Boolean;
use Magento\Ui\Component\Form\Field;

/**
 * @codeCoverageIgnore
 */
class Eav extends EavModifier
{
    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $meta = parent::modifyMeta($meta);

        $attribute = $this->attributeRepository->get('news_from_date');
        if (!$attribute->getId()) {
            return $meta;
        }

        $scopeLabel = '';
        if ($attribute->isScopeGlobal()) {
            $scopeLabel = '[GLOBAL]';
        } elseif ($attribute->isScopeWebsite()) {
            $scopeLabel = '[WEBSITE]';
        } elseif ($attribute->isScopeStore()) {
            $scopeLabel = '[STORE VIEW]';
        }

        $productIsNewContainer = [
            'children' => [
                'is_new' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Set Product as New'),
                                'formElement' => Checkbox::NAME,
                                'componentType' => Field::NAME,
                                'source' => 'product-details',
                                'dataType' => Boolean::NAME,
                                'prefer' => 'toggle',
                                'valueMap' => [
                                    'true' => "1",
                                    'false' => "0"
                                ],
                                'scopeLabel' => $scopeLabel,
                            ]
                        ]
                    ]
                ]
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Set Product as New'),
                        'sortOrder' => 90,
                        'name' => 'container_is_new',
                        'formElement' => 'container',
                        'componentType' => 'container'
                    ]
                ]
            ]
        ];

        if ($this->locator->getProduct()->getId()) {
            $meta['product-details']['children']['container_is_product_new'] = $productIsNewContainer;
        }

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        $data = parent::modifyData($data);
        $model = $this->locator->getProduct();

        if (isset($data[$model->getId()]['product']['news_from_date'])) {
            $data[$model->getId()]['product']['is_new'] = '1';
        }

        return $data;
    }
}
