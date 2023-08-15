<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\Staging\Updates;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRuleStaging\Model\Staging\Updates\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var DataProvider
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var MockObject
     */
    protected $collectionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);

        $className = Collection::class;
        $this->collectionMock = $this->createMock($className);

        $className = CollectionFactory::class;
        $collectionFactoryMock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionMock);

        $className = DataProvider::class;
        $this->model = $objectManager->getObject(
            $className,
            [
                'name' => 'myName',
                'primaryFieldName' => 'myPrimaryFieldName',
                'requestFieldName' => 'myRequestFieldName',
                'request' => $this->requestMock,
                'collectionFactory' => $collectionFactoryMock
            ]
        );
    }

    /**
     * test the getData() method when no updateId is provided.
     *
     * @return void
     */
    public function testGetDataIfUpdateIdIsNull(): void
    {
        $expectedResult = [
            'totalRecords' => 0,
            'items' => []
        ];

        $this->requestMock->expects($this->once())->method('getParam')->with('update_id')->willReturn(null);

        $this->assertEquals($expectedResult, $this->model->getData());
    }

    /**
     * test the getData() method with a valid updateId.
     *
     * @return void
     */
    public function testGetData(): void
    {
        $updateId = 10;
        $expectedResult = [
            'totalRecords' => 1,
            'items' => [
                'item' => 'value'
            ]
        ];

        $this->requestMock->expects($this->once())->method('getParam')->with('update_id')->willReturn($updateId);

        $selectMock = $this->createMock(Select::class);
        $selectMock->expects($this->once())->method('setPart')->with('disable_staging_preview', true)->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with('created_in = ?', $updateId)->willReturnSelf();

        $this->collectionMock->expects($this->once())->method('getSelect')->willReturn($selectMock);
        $this->collectionMock->expects($this->once())->method('toArray')->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->model->getData());
    }
}
