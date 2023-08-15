<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Framework\App\RequestInterface;
use Magento\Rma\Model\Product\Source;
use Magento\Rma\Ui\DataProvider\Product\Form\Modifier\Rma;

/**
 * Updates form initialization data with proper RMA values
 */
class HelperPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Setting default values according to config settings
     *
     * @param Helper $subject
     * @param ProductInterface $product
     * @param array $productData
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeInitializeFromData(Helper $subject, ProductInterface $product, array $productData): array
    {
        if (isset($productData['use_config_' . Rma::FIELD_IS_RMA_ENABLED])
            && 1 === (int)$productData['use_config_' . Rma::FIELD_IS_RMA_ENABLED]) {
            unset($productData['use_config_' . Rma::FIELD_IS_RMA_ENABLED]);
            $productData[Rma::FIELD_IS_RMA_ENABLED] = Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG;
        } elseif (isset($productData['use_config_' . Rma::FIELD_IS_RMA_ENABLED])
            && (int)$productData[Rma::FIELD_IS_RMA_ENABLED] === Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG) {
            $productData[Rma::FIELD_IS_RMA_ENABLED] = Source::ATTRIBUTE_ENABLE_RMA_NO;
        }
        return [$product, $productData];
    }

    /**
     * Add use default checkbox processing
     *
     * @param Helper $subject
     * @param ProductInterface $product
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitializeFromData(Helper $subject, ProductInterface $product): ProductInterface
    {
        $useDefaults = (array) $this->request->getPost('use_default', []);
        if (array_key_exists(Rma::FIELD_IS_RMA_ENABLED, $useDefaults)
            && $useDefaults[Rma::FIELD_IS_RMA_ENABLED] !== '0') {
            $product->setData(Rma::FIELD_IS_RMA_ENABLED, false);
        }
        return $product;
    }
}
