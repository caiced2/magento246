<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Update;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateSearchResultInterface;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\Update\Cleaner;
use Magento\Staging\Model\Update\Includes\Retriever as IncludesRetriever;
use Magento\Staging\Model\UpdateRepository;
use Magento\Staging\Model\VersionHistoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CleanerTest extends TestCase
{
    /**
     * @var UpdateRepository|MockObject
     */
    private $updateRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var IncludesRetriever|MockObject
     */
    private $includesRetriever;

    /**
     * @var VersionHistoryInterface|MockObject
     */
    private $versionHistory;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Cleaner
     */
    private $cleaner;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->updateRepository = $this->getMockBuilder(UpdateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder = $this->createPartialMock(
            SearchCriteriaBuilder::class,
            ['create', 'addFilter']
        );
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($searchCriteria);
        $this->searchCriteriaBuilder->expects($this->any())->method('addFilter')->willReturnself();

        $this->includesRetriever = $this->getMockBuilder(IncludesRetriever::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->versionHistory = $this->getMockBuilder(VersionHistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cleaner = $this->objectManager->getObject(Cleaner::class, [
            'updateRepository' => $this->updateRepository,
            'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            'includes' => $this->includesRetriever,
            'versionHistory' => $this->versionHistory
        ]);
    }

    /**
     * Checks a test case, when cleaner removes outdated updates.
     *
     * @return void
     * @covers \Magento\Staging\Model\Update\Cleaner::execute
     */
    public function testExecute(): void
    {
        $this->withCurrentHistoryVersion();
        $update1 = [
            'id' => 1491915120,
            'name' => 'Rule 1'
        ];
        $update2 = [
            'id' => 1491915220,
            'name' => 'Rule 2',
            'rollback_id' => 14919152340
        ];
        $updates = [$update1, $update2];
        $getListReturnArgs = [];

        list(, $searchResult) = $this->withRepositoryItems($updates);
        $getListReturnArgs[] = $searchResult;

        $rollbacks = [
            [
                'id' => 14919152340,
                'name' => 'Rollback for Rule 2'
            ]
        ];
        list(, $searchResult) = $this->withRepositoryItems($rollbacks);
        $getListReturnArgs[] = $searchResult;

        $updatesWithRollbacks = [$update2];
        list(, $searchResult) = $this->withRepositoryItems($updatesWithRollbacks);
        $getListReturnArgs[] = $searchResult;

        // no moved updates
        list(, $searchResult) = $this->withRepositoryItems([]);
        $getListReturnArgs[] = $searchResult;
        // no includes
        $this->includesRetriever->method('getIncludes')
            ->willReturn([]);

        $updatesToDelete = [$update1, $update2];
        list($items, $searchResult) = $this->withRepositoryItems($updatesToDelete);
        $getListReturnArgs[] = $searchResult;
        $withArgs = [];

        foreach ($items as $item) {
            $withArgs[] = [$item];
        }
        $this->updateRepository
            ->method('delete')
            ->withConsecutive(...$withArgs);

        $this->updateRepository
            ->method('getList')
            ->willReturnOnConsecutiveCalls(...$getListReturnArgs);

        $this->cleaner->execute();
    }

    /**
     * Checks a test case, when cleaner removes rollbacks in the past without updates.
     *
     * @return void
     * @covers \Magento\Staging\Model\Update\Cleaner::execute
     */
    public function testOutdatedRollbacks(): void
    {
        $this->withCurrentHistoryVersion();
        $getListReturnArgs = [];
        // no active updates
        list(, $searchResult) = $this->withRepositoryItems([]);
        $getListReturnArgs[] = $searchResult;

        $rollback = [
            'id' => 14919152340,
            'name' => 'Rollback in the past'
        ];
        list(, $searchResult) = $this->withRepositoryItems([$rollback]);
        $getListReturnArgs[] = $searchResult;

        // no updates with rollbacks
        list(, $searchResult) = $this->withRepositoryItems([]);
        $getListReturnArgs[] = $searchResult;
        // no moved updates
        list(, $searchResult) = $this->withRepositoryItems([]);
        $getListReturnArgs[] = $searchResult;
        // no includes
        $this->includesRetriever->method('getIncludes')
            ->willReturn([]);

        list($updatesToDelete, $searchResult) = $this->withRepositoryItems([$rollback]);
        $getListReturnArgs[] = $searchResult;

        $this->updateRepository
            ->method('getList')
            ->willReturnOnConsecutiveCalls(...$getListReturnArgs);

        $this->updateRepository->method('delete')
            ->with(array_pop($updatesToDelete));

        $this->cleaner->execute();
    }

    /**
     * Checks a test case, when cleaner does not remove anything.
     *
     * @return void
     * @covers \Magento\Staging\Model\Update\Cleaner::execute
     */
    public function testNothingToRemove(): void
    {
        $this->withCurrentHistoryVersion();

        $update1 = [
            'id' => 1491915120,
            'name' => 'Rule 1'
        ];
        $update2 = [
            'id' => 1491915220,
            'name' => 'Rule 2'
        ];
        $updates = [$update1, $update2];
        $getListReturnArgs = [];
        list(, $searchResult) = $this->withRepositoryItems($updates);
        $getListReturnArgs[] = $searchResult;

        // no rollbacks
        list(, $searchResult) = $this->withRepositoryItems([]);
        $getListReturnArgs[] = $searchResult;
        // no updates with rollbacks
        list(, $searchResult) = $this->withRepositoryItems([]);
        $getListReturnArgs[] = $searchResult;

        // moved to updates
        list(, $searchResult) = $this->withRepositoryItems([
            [
                'id' => 1491915236,
                'moved_to' => 1491915120
            ]
        ]);
        $getListReturnArgs[] = $searchResult;

        // includes
        $this->includesRetriever->method('getIncludes')
            ->willReturn([
                [
                    'id' => 14919152890,
                    'created_in' => 1491915220
                ]
            ]);

        $this->updateRepository->expects(self::never())
            ->method('delete');

        $this->updateRepository
            ->method('getList')
            ->willReturnOnConsecutiveCalls(...$getListReturnArgs);

        $this->cleaner->execute();
    }

    /**
     * Imitates behavior of version history manager, which returns version id as current timestamp.
     *
     * @return void
     */
    private function withCurrentHistoryVersion(): void
    {
        $this->versionHistory->method('getCurrentId')
            ->willReturn(time());
    }

    /**
     * Imitates behavior of UpdateRepository, which returns different set of updates depends on context.
     *
     * @param array $data
     *
     * @return array
     */
    private function withRepositoryItems(array $data): array
    {
        $items = [];
        foreach ($data as $value) {
            $items[$value['id']] = $this->objectManager->getObject(Update::class, ['data' => $value]);
        }

        /** @var UpdateSearchResultInterface|MockObject $searchResult */
        $searchResult = $this->getMockBuilder(UpdateSearchResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResult
            ->method('getItems')
            ->willReturnCallback(function () use ($items) {
                return $items;
            });

        return [$items, $searchResult];
    }
}
