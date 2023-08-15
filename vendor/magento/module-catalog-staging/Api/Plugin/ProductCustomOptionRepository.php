<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Api\Plugin;

use Magento\Catalog\Model\Product\Option;

/**
 * Plugin to support if exists option in the scope of current scheduled update
 */
class ProductCustomOptionRepository
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * ProductCustomOptionRepository constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Staging\Model\VersionManager $versionManager
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Staging\Model\VersionManager $versionManager
    ) {
        $this->productRepository = $productRepository;
        $this->versionManager = $versionManager;
    }

    /**
     * Verify if option exists in the scope of update.
     * @param \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
    ) : void {
        if (!$option->getProductSku()) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('The ProductSku is empty. Set the ProductSku and try again.')
            );
        }
        if ($this->versionManager->isPreviewVersion()) {
            $product = $this->productRepository->get($option->getProductSku());
            if ($option->getOptionId() !== null && $product->getOptionById($option->getOptionId()) === null) {
                $option->setOptionId(null);
                if (!empty($option->getData('values')) || !empty($option->getValues('values'))) {
                    $existingValues = $this->getValues($option);
                    $newValues = [];
                    foreach ($existingValues as $value) {
                        $value['option_type_id'] = null;
                        $newValues[] = $value;
                    }
                    $option->setValues(null);
                    $option->setData('values', $newValues);
                }
            }
        }
    }

    /**
     * Get values from options using magic method or defined method
     *
     * @param Option $option
     * @return array
     */
    private function getValues(Option $option) : array
    {
        $existingValues = [];
        if (!empty($option->getData('values'))) {
            $existingValues = $option->getData('values');
        } elseif (!empty($option->getValues())) {
            /** @var \Magento\Catalog\Model\Product\Option\Value $value */
            foreach ($option->getValues() as $value) {
                $existingValues[] = $value->getData();
            }
        }
        return $existingValues;
    }
}
