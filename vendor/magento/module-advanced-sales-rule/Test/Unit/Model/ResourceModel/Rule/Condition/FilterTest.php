<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\ResourceModel\Rule\Condition;

use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter;
use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    /**
     * @var Filter
     */
    protected $model;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var MockObject|AdapterInterface
     */
    protected $connectionMock;

    /**
     * @var MockObject|Select
     */
    protected $selectMock;

    /**
     * @var MockObject|ResourceConnection
     */
    protected $resourceMock;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->connectionMock = $this->getMockBuilder(Mysql::class)
            ->setMethods(['from', 'select', 'quoteInto', 'fetchAll', 'fetchAssoc', 'delete', 'insertMultiple'])
            ->disableOriginalConstructor()
            ->getMock();
        $selectRendererMock = $this->getMockBuilder(SelectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->setMethods(['where', 'from', 'group'])
            ->setConstructorArgs([$this->connectionMock, $selectRendererMock])
            ->getMock();
        $this->selectMock->expects($this->any())->method('where')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('from')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('group')->willReturnSelf();
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->connectionMock
            ->expects($this->any())
            ->method('quoteInto')
            ->willReturnCallback(
                function ($value) {
                    return "'$value'";
                }
            );

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getResources')
            ->willReturn($this->resourceMock);

        $this->model = $this->objectManager->getObject(
            Filter::class,
            [
                'context' => $contextMock,
            ]
        );
    }

    public function testGetFilterTextGenerators()
    {
        $result = ['dummy'];

        $this->setupResource();

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->selectMock)
            ->willReturn($result);

        $this->assertEquals($result, $this->model->getFilterTextGenerators());
    }

    public function testFilterRules()
    {
        $result = ['1' => '1', '3' => '3'];
        $filterText = ["product:category:4", "true"];

        $this->setupResource();

        $this->connectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with($this->selectMock)
            ->willReturn($result);

        $this->assertEquals(array_keys($result), $this->model->filterRules($filterText));
    }

    public function testDeleteRuleFilters()
    {
        $ruleIdArray = ['1', '2'];

        $this->setupResource();

        $this->connectionMock->expects($this->once())
            ->method('delete')
            ->with(
                'magento_salesrule_filter',
                ['rule_id IN (?)' => $ruleIdArray]
            );
        $this->assertTrue($this->model->deleteRuleFilters($ruleIdArray));
    }

    public function testDeleteRuleFiltersNoRuleId()
    {
        $this->assertFalse($this->model->deleteRuleFilters(null));
    }

    public function testInsertFilters()
    {
        $data = [
            'rule_id' => 1,
            'group_id' => 1,
            'weight' => 1,
            'filter_text' => 'product:category:4',
            'filter_text_generator_class' => Category::class,
            'filter_text_generator_arguments' => [],
        ];
        $this->setupResource();

        $this->connectionMock->expects($this->once())
            ->method('insertMultiple')
            ->with(
                'magento_salesrule_filter',
                $data
            );
        $this->assertTrue($this->model->insertFilters($data));
    }

    public function testInsertFiltersNonArray()
    {
        $this->assertFalse($this->model->insertFilters(null));
    }

    private function setupResource()
    {
        $this->resourceMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->once())
            ->method('getTableName')
            ->with('magento_salesrule_filter', 'default')
            ->willReturn('magento_salesrule_filter');
    }
}
