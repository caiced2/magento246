<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerGraphQl\Model;

use Magento\Banner\Model\Banner;
use Magento\Banner\Model\ResourceModel\Banner\Collection;
use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory;
use Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink;
use Magento\CustomerSegment\Model\CustomerSegmentsProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Class allowing to get list of dynamic blocks
 */
class DynamicBlocks
{
    public const SPECIFIED = 'SPECIFIED';

    public const CART_PRICE_RULE_RELATED = 'CART_PRICE_RULE_RELATED';

    public const CATALOG_PRICE_RULE_RELATED = 'CATALOG_PRICE_RULE_RELATED';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var BannerSegmentLink
     */
    private $bannerSegmentLink;

    /**
     * @var CustomerSegmentsProvider
     */
    private $customerSegmentsProvider;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     * @param Uid $idEncoder
     * @param BannerSegmentLink $bannerSegmentLink
     * @param CustomerSegmentsProvider $customerSegmentsProvider
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        Uid $idEncoder,
        BannerSegmentLink $bannerSegmentLink,
        CustomerSegmentsProvider $customerSegmentsProvider
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->idEncoder = $idEncoder;
        $this->bannerSegmentLink = $bannerSegmentLink;
        $this->customerSegmentsProvider = $customerSegmentsProvider;
    }

    /**
     * Get list of dynamic blocks
     *
     * @param array $input
     * @param int|null $customerId
     * @param int $pageSize
     * @param int $currentPage
     * @param int $websiteId
     * @return Collection
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function getList(array $input, ?int $customerId, int $pageSize, int $currentPage, int $websiteId): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()
            ->columns(['main_table.banner_id', 'mbc.banner_content'])
            ->join(
                ['mbc' => $this->resourceConnection->getTableName('magento_banner_content')],
                'main_table.banner_id = mbc.banner_id',
                []
            );

        $collection = $this->addTypeFilter($collection, $input['type'], $input['dynamic_block_uids'] ?? []);
        $collection = $this->addCustomerSegmentFilter($collection, $customerId, $websiteId);

        if (isset($input['locations'])) {
            $collection = $this->addLocationFilter($collection, $input['locations']);
        }

        $collection->addFieldToFilter('is_enabled', Banner::STATUS_ENABLED);

        $collection->getSelect()->group('main_table.banner_id');
        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);

        return $collection;
    }

    /**
     * Filter dynamic blocks by type
     *
     * @param Collection $collection
     * @param string $type
     * @param array $uids
     * @return Collection
     * @throws GraphQlInputException
     */
    private function addTypeFilter(Collection $collection, string $type, array $uids): Collection
    {
        switch ($type) {
            case self::SPECIFIED:
                if (!empty($uids)) {
                    $ids = $this->prepareDynamicBlockIds($uids);
                    $collection->addFieldToFilter('banner_id', ['in' => $ids]);
                }
                break;
            case self::CART_PRICE_RULE_RELATED:
                $collection->getSelect()->join(
                    ['mbsr' => $this->resourceConnection->getTableName('magento_banner_salesrule')],
                    'main_table.banner_id = mbsr.banner_id',
                    []
                );
                break;
            case self::CATALOG_PRICE_RULE_RELATED:
                $collection->getSelect()->join(
                    ['mbcr' => $this->resourceConnection->getTableName('magento_banner_catalogrule')],
                    'main_table.banner_id = mbcr.banner_id',
                    []
                );
                break;
            default:
                throw new GraphQlInputException(__('Incorrect value of input.type'));
        }
        return $collection;
    }

    /**
     * Filter dynamic blocks by locations
     *
     * @param Collection $collection
     * @param array $locations
     * @return Collection
     */
    private function addLocationFilter(Collection $collection, array $locations): Collection
    {
        $filter = [];

        foreach ($locations as $location) {
            $filter[] = ['like' => new \Zend_Db_Expr("'%{$location}%'")];
        }

        return $collection->addFieldToFilter('types', $filter);
    }

    /**
     * Prepare dynamic block ids
     *
     * @param array $dynamicBlockUids
     * @return array
     * @throws GraphQlInputException
     */
    private function prepareDynamicBlockIds(array $dynamicBlockUids): array
    {
        $ids = [];
        foreach ($dynamicBlockUids as $dynamicBlockUid) {
            $ids[] = $this->idEncoder->decode($dynamicBlockUid);
        }

        return $ids;
    }

    /**
     * Filter dynamic blocks by customer segment
     *
     * @param Collection $collection
     * @param int|null $customerId
     * @param int $websiteId
     * @return Collection
     * @throws GraphQlNoSuchEntityException
     */
    private function addCustomerSegmentFilter(Collection $collection, ?int $customerId, int $websiteId): Collection
    {
        try {
            $customerSegmentIds = $this->customerSegmentsProvider->getCustomerSegmentIdsByCustomerId(
                $customerId,
                $websiteId
            );
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        $this->bannerSegmentLink->addBannerSegmentFilter($collection->getSelect(), $customerSegmentIds);

        return $collection;
    }
}
