<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Plugin\Catalog\Model\ResourceModel;

use Magento\Quote\Model\ResourceModel\Quote;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Product triggers quotes recollect if gitcard amounts were changed
 */
class Product
{
    /**
     * @var Quote
     */
    private $quoteResource;

    /**
     * @param Quote $quoteResource
     */
    public function __construct(
        Quote $quoteResource
    ) {
        $this->quoteResource = $quoteResource;
    }

    /**
     * @param ProductResource $subject
     * @param ProductResource $result
     * @param AbstractModel $product
     * @return \Magento\Catalog\Model\ResourceModel\Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ProductResource $subject,
        ProductResource $result,
        AbstractModel $product
    ) {
        if ($product->getTypeId() === Giftcard::TYPE_GIFTCARD) {
            $originalAmount = $product->getOrigData('giftcard_amounts');
            $amountChanged = $product->getData('giftcard_amounts');
            if ($originalAmount != $amountChanged) {
                $this->quoteResource->markQuotesRecollect($product->getId());
            }
        }

        return $result;
    }
}
