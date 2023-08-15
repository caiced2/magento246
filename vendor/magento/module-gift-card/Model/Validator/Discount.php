<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Validator;

use Laminas\Validator\ValidatorInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Quote\Model\Quote\Item;

/**
 * Class Discount Validator
 */
class Discount implements ValidatorInterface
{
    /**
     * @var []
     */
    protected $messages;

    /**
     * Define if we can apply discount to current item
     *
     * @param Item $item
     * @return bool
     */
    public function isValid($item)
    {
        if (Giftcard::TYPE_GIFTCARD == $item->getProductType()) {
            return false;
        }
        return true;
    }

    /**
     * Method to get messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return [];
    }
}
