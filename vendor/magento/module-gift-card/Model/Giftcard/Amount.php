<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Giftcard;

use Magento\GiftCard\Api\Data\GiftcardAmountInterface;

/**
 * Class Amount
 * @codeCoverageIgnore
 */
class Amount extends \Magento\Framework\Model\AbstractExtensibleModel implements GiftcardAmountInterface
{
    const KEY_WEBSITE_ID = 'website_id';
    const KEY_VALUE = 'value';
    const KEY_WEBSITE_VALUE = 'website_value';
    const ATTRIBUTE_ID = 'attribute_id';

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftCard\Model\ResourceModel\Giftcard\Amount::class);
    }

    /**
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->getData(self::KEY_WEBSITE_ID);
    }

    /**
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId)
    {
        return $this->setData(self::KEY_WEBSITE_ID, $websiteId);
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->getData(self::KEY_VALUE);
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setValue($value)
    {
        return $this->setData(self::KEY_VALUE, $value);
    }

    /**
     * @return float
     */
    public function getWebsiteValue()
    {
        return $this->getData(self::KEY_WEBSITE_VALUE);
    }

    /**
     * @param float $websiteValue
     * @return $this
     */
    public function setWebsiteValue($websiteValue)
    {
        return $this->setData(self::KEY_WEBSITE_VALUE, $websiteValue);
    }

    /**
     * @return \Magento\GiftCard\Api\Data\GiftcardAmountExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param \Magento\GiftCard\Api\Data\GiftcardAmountExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\GiftCard\Api\Data\GiftcardAmountExtensionInterface $extensionAttributes = null
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @return int
     */
    public function getAttributeId()
    {
        return $this->getData(self::ATTRIBUTE_ID);
    }

    /**
     * @param int $attributeId
     * @return $this
     */
    public function setAttributeId($attributeId)
    {
        return $this->setData(self::ATTRIBUTE_ID, $attributeId);
    }
}
