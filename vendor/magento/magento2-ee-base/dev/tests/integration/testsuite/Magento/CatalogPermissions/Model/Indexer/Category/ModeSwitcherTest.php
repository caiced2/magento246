<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Model\Indexer\Category;

use Magento\CatalogPermissions\Model\Indexer\AbstractAction;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\ObjectManagerInterface;
use Magento\Indexer\Console\Command\IndexerSetDimensionsModeCommand;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject as Mock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ModeSwitcherTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var InputInterface|Mock
     */
    private $inputMock;

    /**
     * @var OutputInterface|Mock
     */
    private $outputMock;

    /**
     * Connection adapter
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var CollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * @var IndexerSetDimensionsModeCommand
     */
    private $command;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->inputMock = $this->getMockBuilder(InputInterface::class)->getMockForAbstractClass();
        $this->outputMock = $this->getMockBuilder(OutputInterface::class)->getMockForAbstractClass();
        $resource = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $resource->getConnection();
        $this->groupCollectionFactory = $this->objectManager->get(CollectionFactory::class);
        $this->command = $this->objectManager->get(IndexerSetDimensionsModeCommand::class);
    }

    /**
     * @magentoDataFixture Magento/CatalogPermissions/_files/enable_permissions_for_specific_customer_group.php
     * @return void
     * @throws \Exception
     */
    public function testSwitchModeToCustomerGroup(): void
    {
        $this->inputMock->expects($this->any())
            ->method('getArgument')->willReturnMap([
                ['indexer', 'catalogpermissions_category'],
                ['mode', 'customer_group']
            ]);
        $status = $this->command->run($this->inputMock, $this->outputMock);
        $customerGroupsArr = $this->groupCollectionFactory->create()->getAllIds();
        foreach ($customerGroupsArr as $id) {
            $this->assertTrue(
                $this->connection->isTableExists(
                    AbstractAction::INDEX_TABLE . '_' . $id
                )
            );
            $this->assertTrue(
                $this->connection->isTableExists(
                    AbstractAction::INDEX_TABLE . '_' . $id . AbstractAction::REPLICA_SUFFIX
                )
            );
            $this->assertTrue(
                $this->connection->isTableExists(
                    AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX . '_' . $id
                )
            );
            $this->assertTrue(
                $this->connection->isTableExists(
                    AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
                    . '_' . $id . AbstractAction::REPLICA_SUFFIX
                )
            );
        }
        $this->assertFalse($this->connection->isTableExists(AbstractAction::INDEX_TABLE));
        $this->assertFalse(
            $this->connection->isTableExists(
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
            )
        );
        $this->assertFalse(
            $this->connection->isTableExists(
                AbstractAction::INDEX_TABLE . AbstractAction::REPLICA_SUFFIX
            )
        );
        $this->assertFalse(
            $this->connection->isTableExists(
                AbstractAction::INDEX_TABLE
                . AbstractAction::PRODUCT_SUFFIX . AbstractAction::REPLICA_SUFFIX
            )
        );
        $this->assertEquals(Cli::RETURN_SUCCESS, $status, 'Success');
    }

    /**
     * @magentoDataFixture Magento/CatalogPermissions/_files/enable_permissions_for_specific_customer_group.php
     * @magentoConfigFixture indexer/catalogpermissions_category/dimensions_mode customer_group
     *
     * @return void
     * @throws \Exception
     */
    public function testSwitchModeToNone(): void
    {
        $this->inputMock->expects($this->any())
            ->method('getArgument')->willReturnMap([
                ['indexer', 'catalogpermissions_category'],
                ['mode', 'none']
            ]);
        $status = $this->command->run($this->inputMock, $this->outputMock);
        $customerGroupsArr = $this->groupCollectionFactory->create()->getAllIds();
        foreach ($customerGroupsArr as $id) {
            $this->assertFalse(
                $this->connection->isTableExists(
                    AbstractAction::INDEX_TABLE . '_' . $id
                )
            );
            $this->assertFalse(
                $this->connection->isTableExists(
                    AbstractAction::INDEX_TABLE . '_' . $id . AbstractAction::REPLICA_SUFFIX
                )
            );
            $this->assertFalse(
                $this->connection->isTableExists(
                    AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX . '_' . $id
                )
            );
            $this->assertFalse(
                $this->connection->isTableExists(
                    AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
                    . '_' . $id . AbstractAction::REPLICA_SUFFIX
                )
            );
        }
        $this->assertTrue($this->connection->isTableExists(AbstractAction::INDEX_TABLE));
        $this->assertTrue(
            $this->connection->isTableExists(
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
            )
        );
        $this->assertTrue(
            $this->connection->isTableExists(
                AbstractAction::INDEX_TABLE . AbstractAction::REPLICA_SUFFIX
            )
        );
        $this->assertTrue(
            $this->connection->isTableExists(
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
                . AbstractAction::REPLICA_SUFFIX
            )
        );
        $this->assertEquals(Cli::RETURN_SUCCESS, $status, 'Success');
    }
}
