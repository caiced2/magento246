<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Plugin\Catalog\Model\Product\Attribute\Backend;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Backend\Price as BackendPrice;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\FormatInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;

/**
 * Price Backend Plugin for Gift Card Open Amount.
 */
class Price
{
    /**
     * @var FormatInterface
     */
    private $localeFormat;

    /**
     * @param FormatInterface $localeFormat
     */
    public function __construct(FormatInterface $localeFormat)
    {
        $this->localeFormat = $localeFormat;
    }

    /**
     * @var bool
     */
    private $validated = false;

    /**
     * Check that Open Amount range is valid.
     *
     * @param BackendPrice $subject
     * @param DataObject $entity
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeValidate(
        BackendPrice $subject,
        DataObject $entity
    ): void {
        if (!$this->validated
            && $entity instanceof ProductInterface
            && $entity->getTypeId() === Giftcard::TYPE_GIFTCARD
            && $entity->getAllowOpenAmount()
        ) {
            list($minAmount, $maxAmount) = $this->getMinMaxAmounts($entity);

            if (is_numeric($minAmount) && is_numeric($maxAmount) && $minAmount > $maxAmount) {
                throw new LocalizedException(__('Please enter a valid price range.'));
            }

            $this->validated = true;
        }
    }

    /**
     * Retrieve minimum and maximum Open Amount from the provided entity.
     *
     * @param ProductInterface $entity
     * @return array
     */
    private function getMinMaxAmounts(ProductInterface $entity): array
    {
        $minAmount = $entity->getOpenAmountMin();
        $maxAmount = $entity->getOpenAmountMax();
        $minAmount = $this->localeFormat->getNumber($minAmount) ?: $minAmount;
        $maxAmount = $this->localeFormat->getNumber($maxAmount) ?: $maxAmount;

        return [$minAmount, $maxAmount];
    }
}
