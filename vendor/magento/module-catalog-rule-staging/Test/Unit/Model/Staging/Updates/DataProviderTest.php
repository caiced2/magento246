<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Model\Staging\Updates;

use Magento\CatalogRule\Model\ResourceModel\Grid\Collection;
use Magento\CatalogRule\Model\ResourceModel\Grid\CollectionFactory;
use Magento\CatalogRuleStaging\Model\Staging\Updates\DataProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
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

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $collectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionMock);

        $this->model = new DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $this->requestMock,
            $collectionFactoryMock
        );
    }

    public function testGetDataIfUpdateIdIsNull()
    {
        $this->requestMock->expects($this->once())->method('getParam')->with('update_id')->willReturn(null);
        $expectedResult = [
            'totalRecords' => 0,
            'items' => []
        ];
        $this->assertEquals($expectedResult, $this->model->getData());
    }

    public function testGetData()
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
