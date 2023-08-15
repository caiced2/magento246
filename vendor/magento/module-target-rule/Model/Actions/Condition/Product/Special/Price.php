<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Actions\Condition\Product\Special;

/**
 * TargetRule Action Product Price (percentage) Condition Model
 */
class Price extends \Magento\TargetRule\Model\Actions\Condition\Product\Special
{
    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param array $data
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
        $this->setType(\Magento\TargetRule\Model\Actions\Condition\Product\Special\Price::class);
        $this->setValue(100);
    }

    /**
     * Retrieve operator select options array
     *
     * @return array
     */
    protected function _getOperatorOptionArray()
    {
        return [
            '==' => __('equal to'),
            '>' => __('more'),
            '>=' => __('equals or greater than'),
            '<' => __('less'),
            '<=' => __('equals or less than')
        ];
    }

    /**
     * Set operator options
     *
     * @return $this
     */
    public function loadOperatorOptions()
    {
        parent::loadOperatorOptions();
        $this->setOperatorOption($this->_getOperatorOptionArray());
        return $this;
    }

    /**
     * Retrieve rule as HTML formated string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'Product Price is %1 %2% of Matched Product(s) Price',
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Retrieve SELECT WHERE condition for product collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\TargetRule\Model\Index $object
     * @param array &$bind
     * @return \Zend_Db_Expr
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConditionForCollection($collection, $object, &$bind)
    {
        /* @var $resource \Magento\TargetRule\Model\ResourceModel\Index */
        $resource = $object->getResource();
        $operator = $this->getOperator();

        $where = $resource->getOperatorBindCondition(
            'price_index.min_price',
            'final_price',
            $operator,
            $bind,
            [['bindPercentOf', $this->getValue()]]
        );
        return new \Zend_Db_Expr(sprintf('(%s)', $where));
    }

    /**
     * @inheritdoc
     */
    public function asArray(array $arrAttributes = [])
    {
        $array = parent::asArray($arrAttributes);
        $array['attribute'] = 'price';
        return $array;
    }
}
