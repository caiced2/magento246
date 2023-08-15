<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardGraphQl\Model\Resolver\Product\Options;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftCard\Model\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option as GiftCardOption;
use Magento\Store\Model\ScopeInterface;

/**
 * Resolver for the gift card options on the Gift Card Product
 */
class GiftCardOptions implements ResolverInterface
{
    /**
     * Option type name
     */
    private const OPTION_TYPE = 'giftcard';

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
     * @inheritdoc
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

        return $this->getCustomOptionsData($product);
    }

    /**
     * Format custom options data
     *
     * @param Product $product
     * @return array
     */
    private function getCustomOptionsData(Product $product): array
    {
        $senderOptions = $this->getSenderOptions();
        $customOptions = array_merge([], $senderOptions);
        if ($product->getGiftcardType() != Giftcard::TYPE_PHYSICAL) {
            $recipientOptions = $this->getRecipientOptions();
            $customOptions = array_merge($customOptions, $recipientOptions);
        }
        if ($this->isMessageAvailable()) {
            $giftMessageOption = $this->getGiftMessageOptions();
            $customOptions = array_merge($customOptions, $giftMessageOption);
        }

        if ($product->getAllowOpenAmount()) {
            $customAmountOption = $this->getCustomAmountOptions($product);
            $customOptions = array_merge($customOptions, $customAmountOption);
        }
        foreach ($customOptions as &$customOption) {
            $customOption['uid'] = $customOption['value']['uid'];
        }

        return $customOptions;
    }

    /**
     * Get Sender Options Data
     *
     * @return array|array[]
     */
    private function getSenderOptions(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Sender Name'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_SENDER_NAME,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_SENDER_NAME
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Sender Email'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_SENDER_EMAIL,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_SENDER_EMAIL
                    ]))
                ]
            ],
        ];
    }

    /**
     * Get Recipient Options data
     *
     * @return array|array[]
     */
    private function getRecipientOptions(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Recipient Name'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_RECIPIENT_NAME,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_RECIPIENT_NAME
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Recipient Email'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_RECIPIENT_EMAIL,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_RECIPIENT_EMAIL
                    ]))
                ]
            ],
        ];
    }

    /**
     * Get Gift Message Options data
     *
     * @return array|array[]
     */
    private function getGiftMessageOptions(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Message'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_MESSAGE,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_MESSAGE
                    ]))
                ]
            ]
        ];
    }

    /**
     * Get Custom Amount Options data
     *
     * @param Product $product
     * @return array|array[]
     */
    private function getCustomAmountOptions($product): array
    {
        return [
            [
                Option::KEY_TITLE => __('Custom Giftcard Amount'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => count($product->getGiftcardAmounts()) ? 0 : 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_CUSTOM_GIFTCARD_AMOUNT,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_CUSTOM_GIFTCARD_AMOUNT
                    ]))
                ]
            ]
        ];
    }

    /**
     * Is Gift Message available for the store
     *
     * @return bool
     */
    public function isMessageAvailable(): bool
    {
        return $this->scopeConfig->isSetFlag(
            Giftcard::XML_PATH_ALLOW_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }
}
