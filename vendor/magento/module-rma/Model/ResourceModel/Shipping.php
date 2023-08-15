<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\ResourceModel;

use Magento\Rma\Model\Spi\TrackResourceInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Rma\Model\Rma as ModelRma;

/**
 * RMA shipping resource model
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Shipping extends AbstractDb implements TrackResourceInterface
{
    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_rma_shipping_label', 'entity_id');
    }

    /**
     * Delete tracking numbers for current rma shipping label
     *
     * @param ModelRma|int $rma
     * @return string
     */
    public function deleteTrackingNumbers($rma)
    {
        if (!is_int($rma)) {
            $rma = $rma->getId();
        }

        $connection = $this->getConnection();

        $where = $connection->quoteInto('rma_entity_id = ? ', $rma);
        $where .= $connection->quoteInto(
            'AND is_admin = ? ',
            \Magento\Rma\Model\Shipping::IS_ADMIN_STATUS_ADMIN_LABEL_TRACKING_NUMBER
        );

        return $connection->delete($this->getTable('magento_rma_shipping_label'), $where);
    }

    /**
     * Perform actions before object save
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Magento\Rma\Model\Shipping $object */
        if ($object->getIsAdmin() === null) {
            $object->setIsAdmin(\Magento\Rma\Model\Shipping::IS_ADMIN_STATUS_USER_TRACKING_NUMBER);
        }

        return parent::_beforeSave($object);
    }
}
