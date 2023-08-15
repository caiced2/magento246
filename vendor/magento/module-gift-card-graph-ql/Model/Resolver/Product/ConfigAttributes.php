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
class ConfigAttributes implements ResolverInterface
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
            $attributesWithConfigPathKeys = [
                'is_redeemable' => GiftcardModel::XML_PATH_IS_REDEEMABLE,
                'lifetime' => GiftcardModel::XML_PATH_LIFETIME,
                'allow_message' => GiftcardModel::XML_PATH_ALLOW_MESSAGE
            ];

            if (isset($product[$field->getName()]) && isset($product['use_config_' . $field->getName()])) {
                $data = $this->overrideValueFromConfig(
                    $product[$field->getName()],
                    $product['use_config_' . $field->getName()],
                    $attributesWithConfigPathKeys[$field->getName()]
                );
            } else {
                $data = isset($product[$field->getName()]) ? $product[$field->getName()] : null;
            }
        }

        return $data;
    }

    /**
     * Override value from attribute with config value
     *
     * @param int $value
     * @param int $configValue
     * @param string $configPath
     * @return bool
     */
    private function overrideValueFromConfig(int $value, int $configValue, string $configPath) : bool
    {
        if ($configValue) {
            $value = $this->scopeConfig->getValue(
                $configPath,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return (bool)$value;
    }
}
