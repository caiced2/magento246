<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CustomerSegment\Model;

use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory;

/**
 * Provide CustomerSegmentsProvider
 */
class CustomerSegmentsProvider
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get customer segment ids by customer id, if customer id is null return segments for visitor
     *
     * @param int|null $customerId
     * @param int $websiteId
     * @return array
     */
    public function getCustomerSegmentIdsByCustomerId(?int $customerId, int $websiteId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addIsActiveFilter(1);
        $collection->addWebsiteFilter($websiteId);

        $customerSegmentIds = [];
        if ($customerId) {
            $collection->addFieldToFilter(
                'apply_to',
                [Segment::APPLY_TO_REGISTERED, Segment::APPLY_TO_VISITORS_AND_REGISTERED]
            );
            foreach ($collection as $segment) {
                if ($segment->validateCustomer($customerId, $websiteId)) {
                    $customerSegmentIds[] = $segment->getId();
                }
            }
        } else {
            $collection->addFieldToFilter(
                'apply_to',
                [Segment::APPLY_TO_VISITORS, Segment::APPLY_TO_VISITORS_AND_REGISTERED]
            );
            foreach ($collection as $segment) {
                $conditions = $segment->getConditions()->asArray();
                if (empty($conditions['conditions'])) {
                    $customerSegmentIds[] = $segment->getId();
                }
            }
        }

        return $customerSegmentIds;
    }
}
