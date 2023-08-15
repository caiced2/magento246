<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\ResourceModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Module\Manager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModelRole;
use Magento\TargetRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleTest extends TestCase
{
    /**
     * Tested model
     *
     * @var ResourceModelRole
     */
    protected $resourceRule;

    /**
     * Module manager mock
     *
     * @var Manager|MockObject
     */
    protected $moduleManagerMock;

    /**
     * Event manager mock
     *
     * @var ManagerInterface|MockObject
     */
    protected $eventManagerMock;

    /**
     * Cache context mock
     *
     * @var CacheContext|MockObject
     */
    protected $cacheContextMock;

    /**
     * Rule Model mock
     *
     * @var AbstractModel|MockObject
     */
    protected $ruleModelMock;

    /**
     * DB Adapter mock
     *
     * @var AdapterInterface|MockObject
     */
    protected $connectionMock;

    /**
     * App resource mock
     *
     * @var ResourceConnection|MockObject
     */
    protected $appResourceMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->moduleManagerMock = $this->createMock(Manager::class);
        $this->eventManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->cacheContextMock = $this->createMock(CacheContext::class);
        $this->ruleModelMock = $this->createMock(Rule::class);

        $this->connectionMock = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(
                [
                    'delete',
                    'insertOnDuplicate',
                    'describeTable',
                    'lastInsertId',
                    'beginTransaction',
                    'commit',
                    'rollback',
                    'quoteInto'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock->method('describeTable')->willReturn([]);
        $this->connectionMock->method('lastInsertId')->willReturn(1);

        $this->appResourceMock = $this->createMock(ResourceConnection::class);
        $this->appResourceMock->method('getConnection')->willReturn($this->connectionMock);

        $contextMock = $this->createMock(Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->appResourceMock);

        $this->resourceRule = (new ObjectManager($this))->getObject(
            ResourceModelRole::class,
            [
                'moduleManager' => $this->moduleManagerMock,
                'eventManager' => $this->eventManagerMock,
                'cacheContext' => $this->cacheContextMock,
                'context' => $contextMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testSaveCustomerSegments(): void
    {
        $ruleId = 1;
        $segmentIds = [1, 2];

        $this->connectionMock
            ->method('insertOnDuplicate')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->once())
            ->method('delete')
            ->with($this->resourceRule->getTable('magento_targetrule_customersegment'))->willReturnSelf();

        $this->resourceRule->saveCustomerSegments($ruleId, $segmentIds);
    }

    /**
     * @return void
     */
    public function testCleanCachedDataByProductIds(): void
    {
        $productIds = [1, 2, 3];
        $this->moduleManagerMock->expects($this->once())
            ->method('isEnabled')
            ->with('Magento_PageCache')
            ->willReturn(true);

        $this->cacheContextMock->expects($this->once())
            ->method('registerEntities')
            ->with(Product::CACHE_TAG, $productIds)->willReturnSelf();

        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('clean_cache_by_tags', ['object' => $this->cacheContextMock])->willReturnSelf();

        $this->resourceRule->cleanCachedDataByProductIds($productIds);
    }

    /**
     * @return void
     */
    public function testBindRuleToEntity(): void
    {
        $this->appResourceMock
            ->method('getTableName')
            ->with('magento_targetrule_product')
            ->willReturn('magento_targetrule_product');

        $this->connectionMock
            ->method('insertOnDuplicate')
            ->with(
                'magento_targetrule_product',
                [
                    [
                        'product_id' => 1,
                        'rule_id' => 1
                    ]
                ],
                ['rule_id']
            );

        $this->connectionMock->expects($this->never())
            ->method('beginTransaction');
        $this->connectionMock->expects($this->never())
            ->method('commit');
        $this->connectionMock->expects($this->never())
            ->method('rollback');

        $this->resourceRule->bindRuleToEntity([1], [1], 'product');
    }
}
