<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

use Magento\CatalogPermissions\App\Backend\Config;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\CatalogPermissions\Model\Indexer\Plugin\GroupRepository as GroupRepositoryPlugin;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\CatalogPermissions\Model\Indexer\UpdateIndexInterface;
use Magento\Customer\Model\Data\Group;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class to test plugin of Customer Group Repository
 */
class GroupRepositoryTest extends TestCase
{
    /**
     * @var IndexerInterface|MockObject
     */
    protected $indexerMock;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $appConfigMock;

    /**
     * @var GroupRepositoryPlugin
     */
    protected $groupRepositoryPlugin;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var UpdateIndexInterface|MockObject
     */
    private $updateIndexMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->indexerMock = $this->createPartialMock(
            Indexer::class,
            ['getId', 'load', 'invalidate']
        );

        $this->appConfigMock = $this->createPartialMock(
            Config::class,
            ['isEnabled']
        );

        $this->indexerRegistryMock = $this->createPartialMock(
            IndexerRegistry::class,
            ['get']
        );

        $this->updateIndexMock = $this->createMock(
            UpdateIndexInterface::class
        );

        $this->groupRepositoryPlugin = new GroupRepositoryPlugin(
            $this->indexerRegistryMock,
            $this->appConfigMock,
            $this->updateIndexMock
        );
    }

    /**
     * Test to skip invalidate indexer after customer group delete
     */
    public function testAfterDeleteGroupIndexerOff()
    {
        $customerGroupService = $this->createMock(GroupRepository::class);
        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->indexerRegistryMock->expects($this->never())->method('get');
        $this->groupRepositoryPlugin->afterDelete($customerGroupService);
    }

    /**
     * Test to invalidate indexer after customer group delete
     */
    public function testAfterDeleteIndexerOn()
    {
        $customerGroupService = $this->createMock(GroupRepository::class);
        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo(Category::INDEXER_ID)],
                [$this->equalTo(Product::INDEXER_ID)]
            )
            ->willReturn($this->indexerMock);
        $this->indexerMock->expects($this->exactly(2))->method('invalidate');
        $this->groupRepositoryPlugin->afterDelete($customerGroupService);
    }

    /**
     * Test to invalidate indexer on customer group save
     *
     * @param bool $isEnabled
     * @param int|null $id
     * @param bool $needInvalidate
     * @dataProvider aroundSaveInvalidatingDataProvider
     */
    public function testAroundSaveInvalidating(bool $isEnabled, ?int $id, bool $needInvalidate)
    {
        /** @var GroupRepository $customerGroupService */
        $customerGroupService = $this->createMock(GroupRepository::class);
        /** @var Group $customerGroup */
        $customerGroup = $this->createPartialMock(Group::class, ['getId']);
        $customerGroup->method('getId')->willReturn($id);
        $this->appConfigMock->method('isEnabled')->willReturn($isEnabled);
        if ($needInvalidate) {
            $this->updateIndexMock->expects($this->once())
                ->method('update')
                ->with($customerGroup, $needInvalidate);
        } else {
            $this->updateIndexMock->expects($this->never())->method('update');
        }
        $proceedMock = function ($customerGr) {
            return $customerGr;
        };

        $this->assertEquals(
            $customerGroup,
            $this->groupRepositoryPlugin->aroundSave($customerGroupService, $proceedMock, $customerGroup)
        );
    }

    /**
     * @return array
     */
    public function aroundSaveInvalidatingDataProvider(): array
    {
        return [
            [false, null, false],
            [false, 1, false],
            [true, null, true],
            [true, 0, false],
            [true, 1, false],
        ];
    }
}
