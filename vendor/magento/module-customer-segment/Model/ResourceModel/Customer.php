<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\ResourceModel;

use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime;

/**
 * Resource model for customer and customer segment relation model.
 *
 * @api
 * @since 100.0.2
 */
class Customer extends AbstractDb
{
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var Share
     */
    private $customerSharingConfig;
    /**
     * @param Context $context
     * @param DateTime $dateTime
     * @param string $connectionName
     * @param Share $customerSharingConfig
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        $connectionName = null,
        Share $customerSharingConfig = null
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($context, $connectionName);
        $this->customerSharingConfig = $customerSharingConfig ?? ObjectManager::getInstance()->get(Share::class);
    }

    /**
     * Intialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_customersegment_customer', 'customer_id');
    }

    /**
     * Save relations between customer id and segment ids with specific website id
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return $this
     */
    public function addCustomerToWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        $now = $this->dateTime->formatDate(time(), true);
        foreach ($segmentIds as $segmentId) {
            $data = [
                'segment_id' => $segmentId,
                'customer_id' => $customerId,
                'added_date' => $now,
                'updated_date' => $now,
                'website_id' => $websiteId,
            ];
            $this->getConnection()->insertOnDuplicate($this->getMainTable(), $data, ['updated_date']);
        }
        return $this;
    }

    /**
     * Remove relations between customer id and segment ids on specific website
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return $this
     */
    public function removeCustomerFromWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        if (!empty($segmentIds)) {
            $this->getConnection()->delete(
                $this->getMainTable(),
                ['customer_id=?' => $customerId, 'website_id=?' => $websiteId, 'segment_id IN(?)' => $segmentIds]
            );
        }
        return $this;
    }

    /**
     * Get segment ids assigned to customer id on specific website
     *
     * @param int $customerId
     * @param int $websiteId
     * @return array
     */
    public function getCustomerWebsiteSegments($customerId, $websiteId)
    {
        $select = $this
            ->getConnection()
            ->select()
            ->distinct()
            ->from(
                ['segment' => $this->getTable('magento_customersegment_segment')],
                'segment.segment_id'
            )
            ->join(
                ['segment_customer' => $this->getMainTable()],
                'segment.segment_id = segment_customer.segment_id',
                ''
            )
            ->where(
                'segment.is_active = 1'
            )
            ->where(
                'segment_customer.customer_id = :customer_id'
            );

        if ($this->customerSharingConfig->isWebsiteScope()) {
            $select->where(
                'segment_customer.website_id = :website_id'
            );
        } else {
            $select
                ->join(
                    ['segment_website' => $this->getTable('magento_customersegment_website')],
                    'segment.segment_id = segment_website.segment_id',
                    ''
                )
                ->where(
                    'segment_website.website_id = :website_id'
                );
        }

        $bind = [':customer_id' => $customerId, ':website_id' => $websiteId];

        return $this->getConnection()->fetchCol($select, $bind);
    }
}
