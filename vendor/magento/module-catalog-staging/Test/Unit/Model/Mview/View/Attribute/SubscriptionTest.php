<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Mview\View\Attribute;

use Magento\CatalogStaging\Model\Mview\View\Attribute\Subscription as SubscriptionModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Mview\Config;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\CollectionInterface;
use Magento\Framework\Mview\View\StateInterface;
use Magento\Framework\Mview\ViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class SubscriptionTest - unit test for attribute subscription model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubscriptionTest extends TestCase
{
    /**
     * Mysql PDO DB adapter mock
     *
     * @var MockObject|Mysql
     */
    protected $connectionMock;

    /**
     * @var SubscriptionModel
     */
    protected $model;

    /**
     * @var MockObject|ResourceConnection
     */
    protected $resourceMock;

    /**
     * @var MockObject|TriggerFactory
     */
    protected $triggerFactoryMock;

    /**
     * @var MockObject|CollectionInterface
     */
    protected $viewCollectionMock;

    /**
     * @var MockObject|ViewInterface
     */
    protected $viewMock;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var EntityMetadataInterface
     */
    private $entityMetadataMock;

    /**
     * @var MetadataPool
     */
    private $entityMetadataPoolMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(Mysql::class);
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturnArgument(0);
        $this->resourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->triggerFactoryMock = $this->createMock(TriggerFactory::class);
        $this->viewCollectionMock = $this->getMockForAbstractClass(
            CollectionInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->tableName = 'test_table';
        $mviewConfigMock = $this->createMock(Config::class);
        $mviewConfigMock->expects($this->any())
            ->method('getView')
            ->willReturn(['subscriptions' => [$this->tableName => []]]);
        $this->viewMock = $this->getMockForAbstractClass(ViewInterface::class, [], '', false, false, true, []);
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);

        $entityInterface = 'EntityInterface';
        $this->entityMetadataPoolMock = $this->createMock(MetadataPool::class);

        $this->entityMetadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $this->entityMetadataMock->expects($this->any())
            ->method('getEntityTable')
            ->willReturn('entity_table');

        $this->entityMetadataMock->expects($this->any())
            ->method('getIdentifierField')
            ->willReturn('entity_identifier');

        $this->entityMetadataMock->expects($this->any())
            ->method('getLinkField')
            ->willReturn('entity_link_field');

        $this->entityMetadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with($entityInterface)
            ->willReturn($this->entityMetadataMock);

        $this->model = new SubscriptionModel(
            $this->resourceMock,
            $this->triggerFactoryMock,
            $this->viewCollectionMock,
            $this->viewMock,
            $this->tableName,
            'columnName',
            $this->entityMetadataPoolMock,
            $entityInterface,
            [],
            [],
            $mviewConfigMock
        );
    }

    /**
     * Prepare trigger mock.
     *
     * @param string $triggerName
     *
     * @return MockObject
     */
    protected function prepareTriggerMock(string $triggerName): MockObject
    {
        $triggerMock = $this->getMockBuilder(Trigger::class)
            ->onlyMethods(
                [
                    'setName',
                    'getName',
                    'setTime',
                    'setEvent',
                    'setTable',
                    'addStatement'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $triggerMock->expects($this->exactly(3))
            ->method('setName')
            ->with($triggerName)->willReturnSelf();
        $triggerMock->expects($this->exactly(3))
            ->method('getName')
            ->willReturn('triggerName');
        $triggerMock->expects($this->exactly(3))
            ->method('setTime')
            ->with(Trigger::TIME_AFTER)->willReturnSelf();
        $triggerMock->expects($this->exactly(3))
            ->method('setEvent')->willReturnSelf();
        $triggerMock->expects($this->exactly(3))
            ->method('setTable')
            ->with($this->tableName)->willReturnSelf();

        return $triggerMock;
    }

    /**
     * Prepare expected trigger call map.
     *
     * @param MockObject $triggerMock
     *
     * @return MockObject
     */
    protected function prepareTriggerTestCallMap(MockObject $triggerMock): MockObject
    {
        $triggerMock
            ->method('addStatement')
            ->withConsecutive(
                [
                    "SET @entity_id = (SELECT entity_identifier FROM entity_table "
                    . "WHERE entity_link_field = NEW.entity_link_field);\n"
                    . "INSERT IGNORE INTO test_view_cl (entity_id) values(@entity_id);"
                ],
                [
                    "INSERT IGNORE INTO other_test_view_cl (entity_id) values(@entity_id);"
                ],
                [
                    "SET @entity_id = (SELECT entity_identifier FROM entity_table "
                    . "WHERE entity_link_field = NEW.entity_link_field);\n"
                    . "INSERT IGNORE INTO test_view_cl (entity_id) values(@entity_id);"
                ],
                [
                    "INSERT IGNORE INTO other_test_view_cl (entity_id) values(@entity_id);"
                ],
                [
                    "SET @entity_id = (SELECT entity_identifier FROM entity_table "
                    . "WHERE entity_link_field = OLD.entity_link_field);\n"
                    . "INSERT IGNORE INTO test_view_cl (entity_id) values(@entity_id);"
                ],
                [
                    "INSERT IGNORE INTO other_test_view_cl (entity_id) values(@entity_id);"
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $triggerMock,
                $triggerMock,
                $triggerMock,
                $triggerMock,
                $triggerMock,
                $triggerMock
            );

        return $triggerMock;
    }

    /**
     * Prepare changelog mock.
     *
     * @param string $changelogName
     *
     * @return MockObject
     */
    protected function prepareChangelogMock(string $changelogName): MockObject
    {
        $changelogMock = $this->getMockForAbstractClass(
            ChangelogInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $changelogMock->expects($this->exactly(3))
            ->method('getName')
            ->willReturn($changelogName);
        $changelogMock->expects($this->exactly(3))
            ->method('getColumnName')
            ->willReturn('entity_id');

        return $changelogMock;
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $triggerName = 'trigger_name';
        $this->resourceMock->expects($this->atLeastOnce())->method('getTriggerName')->willReturn($triggerName);
        $triggerMock = $this->prepareTriggerMock($triggerName);
        $this->prepareTriggerTestCallMap($triggerMock);
        $changelogMock = $this->prepareChangelogMock('test_view_cl');

        $this->viewMock->expects($this->atLeastOnce())
            ->method('getChangelog')
            ->willReturn($changelogMock);

        $this->triggerFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->willReturn($triggerMock);

        $otherChangelogMock = $this->prepareChangelogMock('other_test_view_cl');

        $otherViewMock = $this->getMockForAbstractClass(
            ViewInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $otherViewMock->expects($this->exactly(1))
            ->method('getId')
            ->willReturn('other_id');
        $otherViewMock->expects($this->exactly(1))
            ->method('getSubscriptions')
            ->willReturn([['name' => $this->tableName], ['name' => 'otherTableName']]);
        $otherViewMock->expects($this->any())
            ->method('getChangelog')
            ->willReturn($otherChangelogMock);

        $this->viewMock->expects($this->exactly(3))
            ->method('getId')
            ->willReturn('this_id');
        $this->viewMock->expects($this->never())
            ->method('getSubscriptions');

        $this->viewCollectionMock->expects($this->exactly(1))
            ->method('getViewsByStateMode')
            ->with(StateInterface::MODE_ENABLED)
            ->willReturn([$this->viewMock, $otherViewMock]);

        $this->connectionMock->expects($this->exactly(3))
            ->method('dropTrigger')
            ->with('triggerName')
            ->willReturn(true);
        $this->connectionMock->expects($this->exactly(3))
            ->method('createTrigger')
            ->with($triggerMock);

        $this->model->create();
    }
}
