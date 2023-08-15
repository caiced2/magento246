<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Setup;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ResourceModel\Group\CollectionFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;
use Magento\Staging\Model\VersionManagerFactory;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Operation\Update\UpdateEntityVersion;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\SalesRule\Model\Rule;
use Magento\Framework\DB\Select;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\SalesRuleStaging\Setup\SalesRuleMigration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SalesRuleMigrationTest extends TestCase
{
    /**
     * @var SalesRuleMigration
     */
    private $salesRuleMigration;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepository;

    /**
     * @var UpdateInterfaceFactory|MockObject
     */
    private $updateFactory;

    /**
     * @var RuleFactory|MockObject
     */
    private $ruleFactory;

    /**
     * @var VersionManagerFactory|MockObject
     */
    private $versionManagerFactory;

    /**
     * @var CreateEntityVersion|MockObject
     */
    private $createEntityVersion;

    /**
     * @var UpdateEntityVersion|MockObject
     */
    private $updateEntityVersion;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->updateRepository = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->onlyMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->updateFactory = $this->getMockBuilder(UpdateInterfaceFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->versionManagerFactory = $this->getMockBuilder(VersionManagerFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->createEntityVersion = $this->getMockBuilder(CreateEntityVersion::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateEntityVersion = $this->getMockBuilder(UpdateEntityVersion::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();

        $timeZoneMock = $this->getMockForAbstractClass(TimezoneInterface::class);
        $timeZoneMock->method('getConfigTimezone')->willReturn('UTC');
        $objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerMock->expects($this->once())->method('get')->willReturn($timeZoneMock);
        $reflectionClass = new \ReflectionClass(ObjectManager::class);
        $reflectionProperty = $reflectionClass->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($objectManagerMock);

        $this->salesRuleMigration = new SalesRuleMigration(
            $this->updateRepository,
            $this->updateFactory,
            $this->ruleFactory,
            $state,
            $logger,
            $this->versionManagerFactory,
            $this->createEntityVersion,
            $this->updateEntityVersion
        );
    }

    public function testAssignRolePermissions()
    {
        $setup = $this->getMockBuilder(
            SchemaSetupInterface::class
        )->onlyMethods(
            ['getTable', 'getConnection']
        )->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $connection = $this->getMockBuilder(
            AdapterInterface::class
        )->onlyMethods(
            ['select', 'fetchAll']
        )->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $select = $this->getMockBuilder(
            Select::class
        )->onlyMethods(
            ['from',]
        )->disableOriginalConstructor()
            ->getMock();
        $ruleModel = $this->getMockBuilder(
            Rule::class
        )->onlyMethods(
            ['load']
        )->addMethods(
            ['unsRowId']
        )->disableOriginalConstructor()
            ->getMock();
        $update = $this->getMockBuilder(
            UpdateInterface::class
        )->onlyMethods(
            ['setName', 'setStartTime', 'setIsCampaign', 'getStartTime', 'getId', 'getRollbackId']
        )->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $salesruleTable = 'salesrule';
        $rule = [
            'name' => 'rule 1',
            'rule_id' => 1,
            'row_id' => 1,
            'from_date' => '2080-12-01',
            'to_date' => null,
        ];
        $rules = [$rule];
        $updateId = 2147483647;
        $rollbackId = null;
        $setup->expects($this->any())->method('getTable')->with('salesrule')->willReturn($salesruleTable);
        $setup->expects($this->any())->method('getConnection')->willReturn($connection);
        $connection->expects($this->any())->method('select')->willReturn($select);
        $connection->expects($this->any())->method('fetchAll')->with($select)->willReturn($rules);
        $select->expects($this->any())->method('from')->with(
            $salesruleTable,
            ['row_id', 'rule_id', 'name', 'from_date', 'to_date', 'is_active']
        )->willReturnSelf();
        $this->ruleFactory->expects($this->any())->method('create')->willReturn($ruleModel);
        $ruleModel->expects($this->any())->method('load')->with($rule['rule_id'])->willReturnSelf();
        $ruleModel->expects($this->any())->method('unsRowId')->willReturnSelf();
        $this->updateFactory->expects($this->any())->method('create')->willReturn($update);
        $update->expects($this->any())->method('setName')->with($rule['name'])->willReturnSelf();
        $setStartTime = $rule['from_date'] . ' 00:00:00';
        $update->expects($this->any())->method('setStartTime')->with($setStartTime)->willReturnSelf();
        $update->expects($this->any())->method('getStartTime')->willReturn($setStartTime);
        $update->expects($this->any())->method('setIsCampaign')->with(false)->willReturnSelf();
        $update->expects($this->any())->method('getId')->willReturn($updateId);
        $update->expects($this->any())->method('getRollbackId')->willReturn($rollbackId);
        $this->updateRepository->expects($this->any())->method('save')->with($update)->willReturnSelf();
        $this->updateEntityVersion->expects($this->any())->method('execute')->with(
            $ruleModel,
            [
                'created_in' => VersionManager::MIN_VERSION,
                'updated_in' => $updateId,
                'row_id' => $rule['row_id'],
            ]
        )->willReturnSelf();
        $this->createEntityVersion->expects($this->any())->method('execute')->with(
            $ruleModel,
            [
                'created_in' => $updateId,
                'updated_in' => $rollbackId,
            ]
        )->willReturnSelf();

        $this->salesRuleMigration->updateRules($setup);
    }
}
