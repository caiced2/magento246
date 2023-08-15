<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Model\ResourceModel\DynamicBlock\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Banner\Model\ResourceModel\Banner\Collection as DynamicBlockCollection;

/**
 * Collection for displaying grid of cms dynamic blocks
 */
class Collection extends DynamicBlockCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    private $aggregations;

    /**
     * @var \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink
     */
    private $bannerSegmentLink;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection
     */
    private $customerSegmentCollection;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param string $mainTable
     * @param string $eventPrefix
     * @param string $eventObject
     * @param string $resourceModel
     * @param \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection $customerSegmentCollection
     * @param string $model
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|string|null $connection
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink,
        \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection $customerSegmentCollection,
        $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
        $connection = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->bannerSegmentLink = $bannerSegmentLink;
        $this->customerSegmentCollection = $customerSegmentCollection;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    /**
     * @inheritdoc
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @inheritdoc
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad()
    {
        $this->addStoresVisibility();
        parent::_afterLoad();
        return $this->addRelatedSegments();
    }

    /**
     * Adds to collection information about related banner segments
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addRelatedSegments()
    {
        $bannerIds = $this->getColumnValues('banner_id');
        $bannersSegments = [];

        if (!empty($bannerIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()->from(
                $this->bannerSegmentLink->getMainTable()
            )->where(
                'banner_id IN(?)',
                $bannerIds
            );

            $bannersRaw = $connection->fetchAll($select);
            $segmentIds = array_column($bannersRaw, 'segment_id');

            if (!empty($segmentIds)) {
                $select = $this->getConnection()->select()->reset();
                $select->from($this->customerSegmentCollection->getMainTable(), ['segment_id', 'name'])
                    ->where('segment_id', ['in' => array_unique($segmentIds)]);
                $segments = $connection->fetchPairs($select);
            }

            foreach ($bannersRaw as $item) {
                $bannerId = $item['banner_id'];
                $segmentName = $segments[$item['segment_id']] ?? '';
                $bannersSegments[$bannerId][] = $segmentName;
            }
        }

        foreach ($this as $item) {
            if (isset($bannersSegments[$item->getBannerId()])) {
                $item->setCustomerSegments(implode(', ', $bannersSegments[$item->getBannerId()]));
            } else {
                $item->setCustomerSegments(__('All Segments'));
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function processBannerStores($bannersStores)
    {
        foreach ($this as $item) {
            if (isset($bannersStores[$item->getBannerId()])) {
                $item->setStores($bannersStores[$item->getBannerId()]);
            } else {
                $item->setStores([]);
            }
        }
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setItems(array $items = null)
    {
        return $this;
    }
}
