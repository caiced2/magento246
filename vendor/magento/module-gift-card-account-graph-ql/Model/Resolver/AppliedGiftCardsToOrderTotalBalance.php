<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccountGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftCardAccountGraphQl\Model\Money\Formatter as MoneyFormatter;

/**
 * Resolver for total applied gift card balance to order
 */
class AppliedGiftCardsToOrderTotalBalance implements ResolverInterface
{
    /**
     * @var MoneyFormatter
     */
    private $moneyFormatter;

    /**
     * @param MoneyFormatter $moneyFormatter
     */
    public function __construct(MoneyFormatter $moneyFormatter)
    {
        $this->moneyFormatter = $moneyFormatter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value)) {
            throw new LocalizedException(__('value should be specified'));
        }

        $store = $context->getExtensionAttributes()->getStore();
        $order = $value['model'];

        $appliedBalance = $order->getBaseGiftCardsAmount();

        if ($appliedBalance) {
            $appliedBalance = $this->moneyFormatter->formatAmountAsMoney($appliedBalance, $store);
        }

        return $appliedBalance;
    }
}
