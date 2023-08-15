<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Ui;

use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesArchive\Model\ResourceModel\Archive;
use Magento\SalesArchive\Ui\ArchiveDataProvider;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchiveDataProviderTest extends TestCase
{
    /**
     * Default value for DataProvider
     */
    const DEFAULT_DATA_PROVIDER = 'default_data_provider';

    /**
     * Value for archive DataProvider
     */
    const ARCHIVE_DATA_PROVIDER = 'archive_data_provider';

    /**
     * @var ArchiveDataProvider|MockObject
     */
    private $archiveDataProvider;

    /**
     * @var \Magento\SalesArchive\Model\ResourceModel\Archive|MockObject
     */
    private $archiveResourceModelMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var SearchCriteria|MockObject
     */
    private $searchCriteriaMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock = $this->createMock(
            SearchCriteriaBuilder::class
        );
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        $this->archiveResourceModelMock = $this->createMock(Archive::class);
        $this->archiveDataProvider = $objectManager->getObject(ArchiveDataProvider::class, [
            'name' => self::DEFAULT_DATA_PROVIDER,
            'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
            'archiveResourceModel' => $this->archiveResourceModelMock,
            'archiveDataSource' => self::ARCHIVE_DATA_PROVIDER
        ]);
    }

    /**
     * @param bool $isOrderInArchive
     * @param int $setRequestNameCount
     * @param IsEqual[] $setRequestNameValues
     *
     * @dataProvider getSearchCriteriaDataProvider
     */
    public function testGetSearchCriteria($isOrderInArchive, $setRequestNameCount, array $setRequestNameValues)
    {
        $this->archiveResourceModelMock->expects($this->once())
            ->method('isOrderInArchive')
            ->willReturn($isOrderInArchive);

        $this->searchCriteriaMock->expects($this->exactly($setRequestNameCount))
            ->method('setRequestName')
            ->withConsecutive(...$setRequestNameValues);

        $this->archiveDataProvider->getSearchCriteria();
    }

    public function getSearchCriteriaDataProvider()
    {
        return [
            'Order in Archive' => [
                'isOrderInArchive' => true,
                'setRequestNameCount' => 2,
                'setRequestNameValues' => [
                    [$this->equalTo(self::DEFAULT_DATA_PROVIDER)],
                    [$this->equalTo(self::ARCHIVE_DATA_PROVIDER)]
                ]
            ],
            'Order not in Archive' => [
                'isOrderInArchive' => false,
                'setRequestNameCount' => 1,
                'setRequestNameValues' => [
                    [$this->equalTo(self::DEFAULT_DATA_PROVIDER)]
                ]
            ]
        ];
    }
}
