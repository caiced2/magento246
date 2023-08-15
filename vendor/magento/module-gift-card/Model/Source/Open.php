<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Source;

class Open extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Eav entity attribute factory
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory
     */
    protected $_eavAttributeFactory;

    /**
     * Resource helper
     *
     * @var \Magento\Eav\Model\ResourceModel\Helper
     */
    protected $_resourceHelper;

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory $eavAttributeFactory
     * @param \Magento\Eav\Model\ResourceModel\Helper $resourceHelper
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory $eavAttributeFactory
    ) {
        $this->_resourceHelper = $resourceHelper;
        $this->_eavAttributeFactory = $eavAttributeFactory;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $result = [];
        foreach ($this->_getValues() as $k => $v) {
            $result[] = ['value' => $k, 'label' => $v];
        }

        return $result;
    }

    /**
     * Get option text
     *
     * @param int|string $value
     * @return null|string
     */
    public function getOptionText($value)
    {
        $options = $this->_getValues();
        if (isset($options[$value])) {
            return $options[$value];
        }
        return null;
    }

    /**
     * Get values
     *
     * @return array
     */
    protected function _getValues()
    {
        return [
            \Magento\GiftCard\Model\Giftcard::OPEN_AMOUNT_DISABLED => __('No'),
            \Magento\GiftCard\Model\Giftcard::OPEN_AMOUNT_ENABLED => __('Yes')
        ];
    }

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeDefaultValue = $this->getAttribute()->getDefaultValue();
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $attributeType = $this->getAttribute()->getBackendType();
        $isNullable = $attributeDefaultValue === null || empty($attributeDefaultValue);

        return [
            $attributeCode => [
                'unsigned' => false,
                'extra' => null,
                'default' => $isNullable ? null : $attributeDefaultValue,
                'type' => $this->_resourceHelper->getDdlTypeByColumnType($attributeType),
                'nullable' => $isNullable,
                'comment' => 'Enterprise Giftcard Open ' . $attributeCode . ' column',
            ],
        ];
    }

    /**
     * Retrieve select for flat attribute update
     *
     * @param int $store
     * @return \Magento\Framework\DB\Select|null
     * @codeCoverageIgnore
     */
    public function getFlatUpdateSelect($store)
    {
        return $this->_eavAttributeFactory->create()->getFlatUpdateSelect($this->getAttribute(), $store);
    }
}
