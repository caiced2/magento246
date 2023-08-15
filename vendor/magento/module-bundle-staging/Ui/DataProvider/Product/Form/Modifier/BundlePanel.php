<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BundleStaging\Ui\DataProvider\Product\Form\Modifier;

use Magento\Ui\Component\Container;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form;
use Magento\Ui\Component\Modal;

/**
 * Class for Product Form Modifier BundlePanel
 */
class BundlePanel extends \Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\BundlePanel
{
    /**
     * @inheritdoc
     */
    protected function getBundleHeader()
    {
        $result = parent::getBundleHeader();
        $result['children']['add_button']['arguments']['data']['config']['actions'] = [
            [
                'targetName' => 'ns = ${ $.ns }, index =' . self::CODE_BUNDLE_OPTIONS,
                '__disableTmpl' => ['targetName' => false],
                'actionName' => 'addChild',
            ]
        ];
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $meta = parent::modifyMeta($meta);

        $meta[self::CODE_BUNDLE_DATA]['children']['modal']['arguments']['data']['config']['provider'] =
            'catalogstaging_update_form.catalogstaging_update_form_data_source';
        $meta[self::CODE_BUNDLE_DATA]['children']['modal']['arguments']['data']['config']['options']['buttons'] = [
            [
                'text' => __('Cancel'),
                'class' => 'action-secondary',
                'actions' => ['closeModal'],
            ],
            [
                'text' => __('Add Selected Products'),
                'class' => 'action-primary',
                'actions' => [
                    [
                        'targetName' => 'ns = bundle_update_product_listing, index = bundle_update_product_listing',
                        'actionName' => 'save'
                    ],
                    'closeModal'
                ],
            ],
        ];
        $meta[self::CODE_BUNDLE_DATA]['children']['modal']['children']['bundle_update_product_listing'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'autoRender' => false,
                        'componentType' => 'insertListing',
                        'dataScope' => 'bundle_update_product_listing',
                        'externalProvider' =>
                            'bundle_update_product_listing.bundle_update_product_listing_data_source',
                        'selectionsProvider' =>
                            'bundle_update_product_listing.bundle_update_product_listing.product_columns.ids',
                        'ns' => 'bundle_update_product_listing',
                        'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'realTimeLink' => false,
                        'dataLinks' => ['imports' => false, 'exports' => true],
                        'behaviourType' => 'simple',
                        'externalFilterMode' => true,
                    ],
                ],
            ],
        ];
        return $meta;
    }

    /**
     * Get Bundle Options structure
     *
     * @return array
     */
    protected function getBundleOptions()
    {
        $result = parent::getBundleOptions();
        $result['children']['record']['children']['product_bundle_container']['children']['bundle_selections']
        ['arguments']['data']['config']['provider'] =
            'catalogstaging_update_form.catalogstaging_update_form_data_source';
        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function getModalSet()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'sortOrder' => 60,
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'dataScope' => 'bundle_button_proxy',
                        'component' => 'Magento_Catalog/js/bundle-proxy-button',
                        'provider' => 'catalogstaging_update_form.catalogstaging_update_form_data_source',
                        'listingDataProvider' => 'bundle_update_product_listing',
                        'actions' => [
                            [
                                'targetName' => 'catalogstaging_update_form.catalogstaging_update_form'
                                    . '.bundle-items.modal',
                                'actionName' => 'toggleModal'
                            ],
                            [
                                'targetName' => 'catalogstaging_update_form.catalogstaging_update_form'
                                    . '.bundle-items.modal.bundle_update_product_listing',
                                'actionName' => 'render'
                            ]
                        ],
                        'title' => __('Add Products to Option'),
                    ],
                ],
            ],
        ];
    }
}
