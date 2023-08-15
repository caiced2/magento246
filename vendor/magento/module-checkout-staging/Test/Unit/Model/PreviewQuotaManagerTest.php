<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CheckoutStaging\Test\Unit\Model;

use Magento\CheckoutStaging\Model\PreviewQuotaManager;
use Magento\CheckoutStaging\Model\ResourceModel\PreviewQuota\Collection;
use Magento\CheckoutStaging\Model\ResourceModel\PreviewQuota\CollectionFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartSearchResultsInterface;
use Magento\Store\Model\StoresConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreviewQuotaManagerTest extends TestCase
{
    /**
     * @var StoresConfig|MockObject
     */
    private $storesConfig;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * @var DateTimeFactory|MockObject
     */
    private $dateTimeFactory;

    /**
     * @var PreviewQuotaManager
     */
    private $pqm;

    protected function setUp(): void
    {
        $this->storesConfig = $this->getMockBuilder(
            StoresConfig::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->cartRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->getMockBuilder(
            SearchCriteriaBuilder::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeFactory = $this->getMockBuilder(
            DateTimeFactory::class
        )->getMock();

        $this->collectionFactory->expects(static::once())
            ->method('create')
            ->willReturn($this->collection);

        $this->pqm = new PreviewQuotaManager(
            $this->storesConfig,
            $this->cartRepository,
            $this->searchCriteriaBuilder,
            $this->collectionFactory,
            $this->dateTimeFactory
        );
    }

    public function testFlush()
    {
        $previewQuotas = [1, 3, 5, 7, 9];
        $lifetimes = [
            1 => 60
        ];
        $date = "2500-02-30 00:00:00";

        $this->collection->expects(static::once())
            ->method('getAllIds')
            ->willReturn($previewQuotas);
        $nowDate = $this->getMockBuilder(
            \DateTime::class
        )->disableOriginalConstructor()
            ->getMock();
        $nowDate->expects(static::once())
            ->method('format')
            ->with('Y-m-d H:i:s')
            ->willReturn($date);
        $nowDate->expects(static::once())
            ->method('sub')
            ->willReturnSelf();
        $this->dateTimeFactory->expects(static::once())
            ->method('create')
            ->with('now')
            ->willReturn($nowDate);

        $this->storesConfig->expects(static::once())
            ->method('getStoresConfigByPath')
            ->with(PreviewQuotaManager::QUOTA_LIFETIME_CONFIG_KEY)
            ->willReturn($lifetimes);

        $this->searchCriteriaBuilder->expects(static::exactly(3))
            ->method('addFilter')
            ->willReturnMap(
                [
                    ['entity_id', $previewQuotas, 'in', $this->searchCriteriaBuilder],
                    [CartInterface::KEY_STORE_ID, 1, 'eq', $this->searchCriteriaBuilder],
                    [CartInterface::KEY_UPDATED_AT, $date, 'to', $this->searchCriteriaBuilder]
                ]
            );

        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->getMockForAbstractClass(CartSearchResultsInterface::class);
        $this->searchCriteriaBuilder->expects(static::once())
            ->method('create')
            ->willReturn($searchCriteria);
        $this->cartRepository->expects(static::once())
            ->method('getList')
            ->willReturn($result);
        $result->expects(static::once())
            ->method('getItems')
            ->willReturn([]);

        $this->pqm->flushOutdated();
    }
}
