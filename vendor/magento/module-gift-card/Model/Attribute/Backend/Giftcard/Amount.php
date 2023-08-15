<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Attribute\Backend\Giftcard;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftCard\Ui\DataProvider\Product\Form\Modifier\GiftCard;

class Amount extends \Magento\Catalog\Model\Product\Attribute\Backend\Price
{
    /**
     * Giftcard amount backend resource model
     *
     * @var \Magento\GiftCard\Model\ResourceModel\Attribute\Backend\Giftcard\Amount
     */
    protected $_amountResource;

    /**
     * Validate data
     *
     * @param Product $object
     * @return $this
     * @throws LocalizedException
     */
    public function validate($object)
    {
        $rows = $object->getData($this->getAttribute()->getName());
        if (empty($rows)) {
            if (!$object->getData(GiftCard::FIELD_ALLOW_OPEN_AMOUNT)) {
                throw new LocalizedException(__('Amount should be specified or Open Amount should be allowed'));
            }
            return $this;
        }
        $dup = [];

        foreach ($rows as $row) {
            if (!isset($row['website_value']) || !empty($row['delete'])) {
                continue;
            }

            $key1 = implode('-', [$row['website_id'], $row['website_value']]);

            if (!empty($dup[$key1])) {
                throw new LocalizedException(__('Duplicate amount found.'));
            }
            $dup[$key1] = 1;
        }

        return $this;
    }
}
