<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Factory as ConcreteConditionFactory;

/**
 * Product rule condition data model
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Product extends \Magento\SalesRule\Model\Rule\Condition\Product implements
    \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
{
    /**
     * @var ConcreteConditionFactory
     */
    protected $concreteConditionFactory;

    /**
     * @var \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
     */
    protected $concreteCondition = null;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param ConcreteConditionFactory $concreteConditionFactory,
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        ConcreteConditionFactory $concreteConditionFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $backendData,
            $config,
            $productFactory,
            $productRepository,
            $productResource,
            $attrSetCollection,
            $localeFormat,
            $data
        );
        $this->concreteConditionFactory = $concreteConditionFactory;
    }

    /**
     * Whether this condition can be filtered using index table
     *
     * @return bool
     */
    public function isFilterable()
    {
        if ($this->concreteCondition === null) {
            $this->concreteCondition = $this->concreteConditionFactory->create($this);
        }
        return $this->concreteCondition->isFilterable();
    }

    /**
     * Return a list of filter groups that represent this condition
     *
     * @return FilterGroupInterface[]
     */
    public function getFilterGroups()
    {
        if ($this->concreteCondition === null) {
            $this->concreteCondition = $this->concreteConditionFactory->create($this);
        }
        return $this->concreteCondition->getFilterGroups();
    }
}
