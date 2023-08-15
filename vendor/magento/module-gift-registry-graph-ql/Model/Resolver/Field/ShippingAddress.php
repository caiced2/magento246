<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver\Field;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\CustomerGraphQl\Model\Customer\Address\ExtractCustomerAddressData;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region as RegionResourceModel;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Model\Entity;

/**
 * Resolves the gift registry shipping address
 */
class ShippingAddress implements ResolverInterface
{
    /**
     * @var ExtractCustomerAddressData
     */
    private $extractCustomerAddressData;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var RegionResourceModel
     */
    private $regionResourceModel;

    /**
     * @param ExtractCustomerAddressData $extractCustomerAddressData
     * @param RegionFactory $regionFactory
     * @param RegionResourceModel $regionResourceModel
     */
    public function __construct(
        ExtractCustomerAddressData $extractCustomerAddressData,
        RegionFactory $regionFactory,
        RegionResourceModel $regionResourceModel
    ) {
        $this->extractCustomerAddressData = $extractCustomerAddressData;
        $this->regionFactory = $regionFactory;
        $this->regionResourceModel = $regionResourceModel;
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
            throw new GraphQlInputException(__(
                '"%1" value should be specified',
                ['model']
            ));
        }

        /** @var Entity $model */
        $model = $value['model'];
        $customerId = (int) $context->getUserId() ?? null;

        if ($customerId !== (int) $model->getCustomerId()) {
            return null;
        }

        $addressData = $this->extractCustomerAddressData->execute(
            $model->exportAddressData()
        );

        if (isset($addressData[AddressInterface::STREET]) && !is_array($addressData[AddressInterface::STREET])) {
            $addressData[AddressInterface::STREET] = [$addressData[AddressInterface::STREET]];
        } else {
            $addressData[AddressInterface::STREET] = [];
        }

        $regionData = [];

        if (isset($addressData[RegionInterface::REGION_ID])) {
            $region = $this->regionFactory->create();
            $this->regionResourceModel->load(
                $region,
                $addressData[RegionInterface::REGION_ID]
            );
            $regionData[RegionInterface::REGION] = $region->getName();
            $regionData[RegionInterface::REGION_ID] = $region->getRegionId();
            $regionData[RegionInterface::REGION_CODE] = $region->getCode();
        }

        $addressData[RegionInterface::REGION] = $regionData;

        return $addressData;
    }
}
