<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GiftCard\Model\Giftcard as GiftcardModel;

/**
 * Post formatting for data in the giftcard type products
 *
 * {@inheritdoc}
 */
class MessageMaxLength implements ResolverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Add formatting for the giftcard product type
     *
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];

        $data = null;
        if ($product->getTypeId() === GiftCardType::TYPE_GIFTCARD) {
            $data = $this->scopeConfig->getValue(
                GiftcardModel::XML_PATH_MESSAGE_MAX_LENGTH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        return $data;
    }
}
