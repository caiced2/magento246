<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Config;
use Magento\Support\Model\Report\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var Collection|MockObject
     */
    protected $collectionMock;

    /**
     * @var Config|MockObject
     */
    protected $reportConfigMock;

    /**
     * @var DataProvider
     */
    protected $model;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->reportConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->model = $this->objectManager->getObject(DataProvider::class, [
            'reportConfig' => $this->reportConfigMock,
            'collectionFactory' => $this->collectionFactoryMock,
        ]);
    }

    public function testGetData()
    {
        $groupNames = ['general', 'environment'];
        $expected = [null => ['general' => ['report_groups' => $groupNames]]];

        $this->reportConfigMock->expects($this->once())
            ->method('getGroupNames')
            ->willReturn($groupNames);

        $this->assertEquals($expected, $this->model->getData());
    }
}
