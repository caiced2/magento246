<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Ui\Component\Listing;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\PricePermissions\Observer\ObserverData;

/**
 * Class Columns
 */
class Columns extends \Magento\Catalog\Ui\Component\Listing\Columns
{
    /**
     * @var ObserverData
     */
    private $observerData;

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Catalog\Ui\Component\ColumnFactory $columnFactory
     * @param \Magento\Catalog\Ui\Component\Listing\Attribute\RepositoryInterface $attributeRepository
     * @param ObserverData $observerData
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Catalog\Ui\Component\ColumnFactory $columnFactory,
        \Magento\Catalog\Ui\Component\Listing\Attribute\RepositoryInterface $attributeRepository,
        ObserverData $observerData,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $columnFactory, $attributeRepository, $components, $data);

        $this->observerData = $observerData;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        parent::prepare();

        if ($this->observerData->isCanReadProductPrice()) {
            return;
        }

        /** @var ProductAttributeInterface $attribute */
        foreach ($this->attributeRepository->getList() as $attribute) {
            if ($this->isPrice($attribute)) {
                unset($this->components[$attribute->getAttributeCode()]);
            }
        }
    }

    /**
     * Check is price attribute
     *
     * @param ProductAttributeInterface $attribute
     * @return bool
     */
    private function isPrice(ProductAttributeInterface $attribute)
    {
        return
            $attribute->getFrontendInput() === 'price'
            || $attribute->getAttributeCode() === ProductAttributeInterface::CODE_TIER_PRICE;
    }
}
