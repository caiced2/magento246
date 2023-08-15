<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionValueProvider;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * RMA custom attribute formatter
 */
class CustomAttribute
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var OptionValueProvider
     */
    private $optionValueProvider;

    /**
     * @param SerializerInterface $serializer
     * @param Uid $idEncoder
     * @param OptionValueProvider $optionValueProvider
     */
    public function __construct(
        SerializerInterface $serializer,
        Uid $idEncoder,
        OptionValueProvider $optionValueProvider
    ) {
        $this->serializer = $serializer;
        $this->idEncoder = $idEncoder;
        $this->optionValueProvider = $optionValueProvider;
    }

    /**
     * Format custom attribute according to the GraphQL schema
     *
     * @param AttributeInterface $attribute
     * @param mixed $value
     * @return array
     */
    public function format(AttributeInterface $attribute, $value): array
    {
        if (in_array($attribute->getFrontendInput(), ['select', 'multiselect'], true)) {
            $value = $this->optionValueProvider->get((int)$value);
        }

        return [
            'uid' =>  $this->idEncoder->encode((string)$attribute->getAttributeId()),
            'label' => $attribute->getDefaultFrontendLabel(),
            'value' => $this->serializer->serialize($value)
        ];
    }
}
