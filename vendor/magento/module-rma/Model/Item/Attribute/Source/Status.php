<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\Item\Attribute\Source;

/**
 * RMA Item status attribute model
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Status extends \Magento\Rma\Model\Rma\Source\Status
{
    /**
     * Get available states keys for entities
     *
     * @return string[]
     */
    protected function _getAvailableValues()
    {
        return [
            self::STATE_PENDING,
            self::STATE_AUTHORIZED,
            self::STATE_RECEIVED,
            self::STATE_APPROVED,
            self::STATE_REJECTED,
            self::STATE_DENIED
        ];
    }

    /**
     * Checks is status available
     *
     * @param string $status RMA item status
     * @return boolean
     */
    public function checkStatus($status)
    {
        return in_array($status, $this->_getAvailableValues()) ? true : false;
    }

    /**
     * Checks is status final
     *
     * @param string $status RMA item status
     * @return boolean
     */
    public function isFinalStatus($status)
    {
        return in_array($status, [self::STATE_APPROVED, self::STATE_REJECTED, self::STATE_DENIED]) ? true : false;
    }
}
