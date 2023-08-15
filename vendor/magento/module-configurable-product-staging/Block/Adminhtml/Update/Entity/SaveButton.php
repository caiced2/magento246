<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableProductStaging\Block\Adminhtml\Update\Entity;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveButton provides button-specific settings for 'Save' button
 */
class SaveButton implements ButtonProviderInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * The list of product types that may be configured
     *
     * @var array
     */
    private static $configurableTypes = [
        Configurable::TYPE_CODE,
        Type::TYPE_SIMPLE,
        Type::TYPE_VIRTUAL
    ];

    /**
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        RequestInterface $request,
        ProductRepositoryInterface $productRepository
    ) {
        $this->request = $request;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => $this->getMetadata(),
            'sort_order' => 20,
        ];
    }

    /**
     * Retrieve button specific metadata
     *
     * The button specific metadata may be differ for different products types.
     * Products with configurable types (configurable, simple, virtual) requires specific preprocessing logic
     * for 'save' event.
     *
     * This method retrieves the current product type and provides specific metadata
     * for this particular type.
     *
     * @return array
     */
    private function getMetadata()
    {
        if ($this->isConfigurableProduct()) {
            return $this->getConfigurableProductMetadata();
        }
        return $this->getDefaultMetadata();
    }

    /**
     * Retrieve metadata of 'Save' button for product with configurable type
     *
     * Configurable product types:
     *   - configurable;
     *   - simple;
     *   - virtual.
     *
     * As a result of applying this metadata,
     * the widget \app\code\Magento\Ui\view\base\web\js\form\button-adapter.js will be used
     * to create 'Save' button component.
     *
     * When user makes click on 'Save' button, then the 'save' event occurs and the method serializeData()
     * of \app\code\Magento\ConfigurableProduct\view\adminhtml\web\js\variations\variations.js class will be executed
     * as save form preprocessor.
     *
     * And then the method save() of \app\code\Magento\Ui\view\base\web\js\form\form.js class will be executed.
     *
     * @return array
     */
    private function getConfigurableProductMetadata()
    {
        $targetName = 'catalogstaging_update_form.catalogstaging_update_form.configurableVariations';
        $actionName = 'serializeData';

        return [
            'mage-init' => [
                'buttonAdapter' => [
                    'actions' => [
                        [
                            'targetName' => $targetName,
                            'actionName' => $actionName,
                            'params' => [
                                false,
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * Retrieve default metadata of 'Save' button
     *
     * As a result of applying this metadata,
     * the widget \lib\web\mage\backend\button.js will be used to create 'Save' button component.
     *
     * When user makes click on 'Save' button, then the 'save' event occurs and the method save()
     * of \app\code\Magento\Ui\view\base\web\js\form\form.js class will be executed.
     *
     * @return array
     */
    private function getDefaultMetadata()
    {
        return [
            'mage-init' => [
                'button' => [
                    'event' => 'save',
                ],
            ],
            'form-role' => 'save',
        ];
    }

    /**
     * Check if the current product has a configurable type
     *
     * Gets product ID from request object and gets product type by this product ID.
     * If product has configurable type (configurable, simple, virtual) then return TRUE.
     * Otherwise return FALSE.
     *
     * @return boolean
     */
    private function isConfigurableProduct()
    {
        $product = $this->productRepository->getById($this->request->getParam('id'));
        return in_array($product->getTypeId(), self::$configurableTypes);
    }
}
