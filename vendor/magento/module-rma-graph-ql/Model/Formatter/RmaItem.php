<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Rma\Api\Data\ItemInterface;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\Rma\Source\Status;

/**
 * Rma item formatter
 */
class RmaItem
{
    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @var string
     */
    private $rmaItemStatusEnum = 'ReturnItemStatus';

    /**
     * @var CustomAttribute
     */
    private $customAttributeFormatter;

    /**
     * @var array
     */
    private $systemAttributes = [
        Item::ENTITY_ID,
        Item::RMA_ENTITY_ID,
        Item::ORDER_ITEM_ID,
        Item::QTY_REQUESTED,
        Item::QTY_AUTHORIZED,
        Item::QTY_RETURNED,
        Item::QTY_APPROVED,
        Item::STATUS,
        'extension_attributes'
    ];

    /**
     * @param Uid $idEncoder
     * @param EnumLookup $enumLookup
     * @param CustomAttribute $customAttributeFormatter
     */
    public function __construct(
        Uid $idEncoder,
        EnumLookup $enumLookup,
        CustomAttribute $customAttributeFormatter
    ) {
        $this->idEncoder = $idEncoder;
        $this->enumLookup = $enumLookup;
        $this->customAttributeFormatter = $customAttributeFormatter;
    }

    /**
     * Format RMA item according to the GraphQL schema
     *
     * @param ItemInterface $item
     * @return array
     * @throws RuntimeException
     */
    public function format(ItemInterface $item): array
    {
        $customAttributes = [];

        foreach ($item->getData() as $attributeCode => $value) {
            if (!in_array($attributeCode, $this->systemAttributes, true)) {
                $attribute = $item->getAttribute($attributeCode);
                if (isset($attribute) && $attribute->getIsVisible()) {
                    $customAttributes[] = $this->customAttributeFormatter->format($attribute, $value);
                }
            }
        }

        return [
            'uid' => $this->idEncoder->encode((string)$item->getEntityId()),
            'custom_attributes' => $customAttributes,
            'request_quantity' => (float)$item->getQtyRequested(),
            'quantity' => (float)$item->getQtyAuthorized(),
            'status' => $this->enumLookup->getEnumValueFromField($this->rmaItemStatusEnum, $item->getStatus()),
            'model' => $item
        ];
    }
}
