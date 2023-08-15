<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * GiftCardAccount Resource Collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount\Grid;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\GiftCardAccount\Model\Giftcardaccount
     */
    protected $_model;

    /**
     * @param \Magento\GiftCardAccount\Model\Giftcardaccount $model
     */
    public function __construct(\Magento\GiftCardAccount\Model\Giftcardaccount $model)
    {
        $this->_model = $model;
    }

    /**
     * Return states options list
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_model->getStatesAsOptionList();
    }
}
