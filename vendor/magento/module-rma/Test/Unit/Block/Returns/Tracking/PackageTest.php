<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Returns\Tracking;

use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Returns\Tracking\Package;
use Magento\Rma\Model\Shipping\Info;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests Block to show Rma packages
 */
class PackageTest extends TestCase
{
    /**
     * @var Registry|MockObject
     */
    private $registryMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var Info|MockObject
     */
    private $infoMock;

    /**
     * @var Package
     */
    private $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->infoMock = $this->getMockBuilder(Info::class)
            ->addMethods(['getPackages'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock = $this->createMock(Registry::class);
        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('rma_package_shipping')
            ->willReturn($this->infoMock);
        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);

        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(
            Package::class,
            [
                'registry' => $this->registryMock,
                'serializer' => $this->serializerMock,
            ]
        );
    }

    /**
     * Tests to retrieve packages in Json format
     */
    public function testGetPackages(): void
    {
        $packagesJsonStr = 'json string';
        $packagesArray = ['val1', 'val2'];

        $this->infoMock->method('getPackages')
            ->willReturn($packagesJsonStr);
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($packagesJsonStr)
            ->willReturn($packagesArray);

        $this->assertEquals(
            $packagesArray,
            $this->block->getPackages(),
            'Wrong Rma packages'
        );
    }

    /**
     * Tests to retrieve empty packages
     */
    public function testGetPackagesEmpty(): void
    {
        $this->infoMock->method('getPackages')->willReturn(null);
        $this->serializerMock->expects($this->never())->method('unserialize');

        $this->assertEquals([], $this->block->getPackages(), 'Rma packages list is not empty');
    }
}
