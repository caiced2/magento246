<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Mapper;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\Enum\DataMapperInterface;
use Magento\GiftRegistry\Model\Attribute\Config as AttributeConfig;
use Magento\GiftRegistry\Model\Attribute\Processor;

/**
 * Maps the gift registry fields
 */
class GiftRegistryDataMapper
{
    /**
     * The privacy mapper name
     */
    const GIFT_REGISTRY_PRIVACY_SETTINGS_MAP = 'GiftRegistryPrivacySettings';

    /**
     * The status mapper name
     */
    const GIFT_REGISTRY_STATUS_MAP = 'GiftRegistryStatus';

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var DataMapperInterface
     */
    private $enumDataMapper;

    /**
     * @var AttributeConfig
     */
    private $attributeConfig;

    /**
     * @param Uid $idEncoder
     * @param DataMapperInterface $enumDataMapper
     * @param AttributeConfig $attributeConfig
     */
    public function __construct(
        Uid $idEncoder,
        DataMapperInterface $enumDataMapper,
        AttributeConfig $attributeConfig
    ) {
        $this->idEncoder = $idEncoder;
        $this->enumDataMapper = $enumDataMapper;
        $this->attributeConfig = $attributeConfig;
    }

    /**
     * Mapping gift registry data
     *
     * @param array $giftRegistryInput
     * @return array
     */
    public function map(array $giftRegistryInput): array
    {
        $giftRegistryData = $giftRegistryInput;
        if (isset($giftRegistryData['event_name'])) {
            $giftRegistryData['title'] = $giftRegistryData['event_name'];
        }

        if (isset($giftRegistryData['status'])) {
            $giftRegistryData['is_active'] = $this->getStatus(
                $giftRegistryData['status']
            );
        }

        if (isset($giftRegistryData['privacy_settings'])) {
            $giftRegistryData['is_public'] = $this->getPrivacySetting(
                $giftRegistryData['privacy_settings']
            );
        }

        if (isset($giftRegistryData['gift_registry_type_uid'])) {
            $giftRegistryData['type_id'] = $this->getTypeId(
                $giftRegistryData['gift_registry_type_uid']
            );
        }

        $dynamicAttributes = $giftRegistryData['dynamic_attributes'] ?? [];
        $giftRegistryData += $this->addGiftDynamicAttributes($dynamicAttributes);

        return $giftRegistryData;
    }

    /**
     * Get event privacy
     *
     * Default privacy is 0 - private. Privacy is int in case there are more than 2 privacies
     *
     * @param string $privacy
     *
     * @return int
     */
    private function getPrivacySetting(string $privacy): int
    {
        $enums = $this->enumDataMapper->getMappedEnums(self::GIFT_REGISTRY_PRIVACY_SETTINGS_MAP);

        return (int) $enums[strtolower($privacy)] ?? 0;
    }

    /**
     * Get event status
     *
     * Default status is 0 - disabled. Statuses are int in case there are more than 2 statuses
     *
     * @param string $status
     * @return int
     */
    private function getStatus(string $status): int
    {
        $enums = $this->enumDataMapper->getMappedEnums(self::GIFT_REGISTRY_STATUS_MAP);

        return (int) $enums[strtolower($status)] ?? 0;
    }

    /**
     * Adding additional attributes
     *
     * @param array $dynamicAttributes
     * @return array
     */
    private function addGiftDynamicAttributes(array $dynamicAttributes)
    {
        $attributes = [];

        foreach ($dynamicAttributes as $attribute) {
            if (in_array($attribute['code'], $this->attributeConfig->getStaticTypesCodes())) {
                $attributes[$attribute['code']] = $attribute['value'];
            } else {
                $attributes[Processor::XML_REGISTRY_NODE][$attribute['code']] = $attribute['value'];
            }
        }

        return $attributes;
    }

    /**
     * Get decoded gift registry type ID.
     *
     * @param string $gift_registry_type_uid
     * @return int
     * @throws GraphQlInputException
     */
    private function getTypeId(string $gift_registry_type_uid): int
    {
        return (int) $this->idEncoder->decode($gift_registry_type_uid);
    }
}
