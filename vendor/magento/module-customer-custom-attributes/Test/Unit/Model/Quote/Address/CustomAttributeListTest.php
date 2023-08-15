<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Quote\Address;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerCustomAttributes\Model\Quote\Address\CustomAttributeList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomAttributeListTest extends TestCase
{
    /**
     * @var AddressMetadataInterface|MockObject
     */
    protected $addressMetadata;

    /**
     * @var CustomAttributeList
     */
    protected $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->addressMetadata = $this->getMockForAbstractClass(
            AddressMetadataInterface::class,
            [],
            '',
            false
        );

        $this->model = new CustomAttributeList($this->addressMetadata);
    }

    /**
     * @return void
     */
    public function testGetAttributes(): void
    {
        $customAttributesMetadata = $this->getMockForAbstractClass(
            AttributeMetadataInterface::class,
            [],
            '',
            false
        );

        $customAttributesMetadata
            ->method('getAttributeCode')
            ->willReturnOnConsecutiveCalls('attributeCode', 'customAttributeCode');
        $this->addressMetadata
            ->method('getCustomAttributesMetadata')
            ->withConsecutive([AddressInterface::class], [CustomerInterface::class])
            ->willReturnOnConsecutiveCalls([$customAttributesMetadata], [$customAttributesMetadata]);

        $this->assertEquals(
            [
                'attributeCode' => $customAttributesMetadata,
                'customAttributeCode' => $customAttributesMetadata
            ],
            $this->model->getAttributes()
        );
    }
}
