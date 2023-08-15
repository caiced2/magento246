<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardGraphQl\Model\Resolver\Order\Item;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;

/**
 * Post formatting for data in the giftcard items
 */
class GiftCard implements ResolverInterface
{
    /**
     * Serializer
     *
     * @var Json
     */
    private $serializer;

    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @param ValueFactory $valueFactory
     * @param Json $serializer
     */
    public function __construct(
        ValueFactory $valueFactory,
        Json $serializer
    ) {
        $this->valueFactory = $valueFactory;
        $this->serializer = $serializer;
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
        return $this->valueFactory->create(function () use ($value) {
            if (!isset($value['model'])) {
                throw new LocalizedException(__('"model" value should be specified'));
            }
            if ($value['model'] instanceof OrderItemInterface) {
                return $this->formatGiftcardData($value['model']);
            } elseif ($value['model'] instanceof InvoiceItemInterface
                || $value['model'] instanceof CreditmemoItemInterface
                || $value['model'] instanceof ShipmentItemInterface) {
                $item = $value['model'];
                return $this->formatGiftcardData($item->getOrderItem());
            }
            return null;
        });
    }

    /**
     * Format values from order giftcard item
     *
     * @param OrderItemInterface $item
     * @return array
     */
    private function formatGiftcardData(
        OrderItemInterface $item
    ): array {
        $giftcardData = [];
        if ($item->getProductType() === 'giftcard') {
            $options = $item->getProductOptions();
            $giftcardData = [
                'sender_name' => $options['giftcard_sender_name'] ?? null,
                'sender_email' => $options['giftcard_sender_email'] ?? null,
                'recipient_name' => $options['giftcard_recipient_name'] ?? null,
                'recipient_email' => $options['giftcard_recipient_email'] ?? null,
                'message' => $options['giftcard_message'] ?? null,
            ];
        }
        return $giftcardData;
    }
}
