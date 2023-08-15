<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Sales\Quote;

use Magento\CustomerCustomAttributes\Model\Sales\Quote\Address;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var MockObject
     */
    protected $resourceMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->resourceMock = $this->createMock(
            \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote\Address::class
        );

        $this->address = new Address(
            $this->contextMock,
            $this->registryMock,
            $this->resourceMock
        );
    }

    public function testAttachDataToEntities()
    {
        $entities = ['entity' => 'value'];

        $this->resourceMock->expects($this->once())
            ->method('attachDataToEntities')
            ->with($entities);

        $this->assertEquals($this->address, $this->address->attachDataToEntities($entities));
    }
}
