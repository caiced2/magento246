<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Model\Order\Archive\Grid\Massaction;

class ItemsUpdater extends \Magento\SalesArchive\Model\Order\Grid\Massaction\ItemsUpdater implements
    \Magento\Framework\View\Layout\Argument\UpdaterInterface
{
    /**
     * Remove massaction items in case they disallowed for user
     *
     * @param mixed $argument
     * @return mixed
     */
    public function update($argument)
    {
        if ($this->_salesArchiveConfig->isArchiveActive()) {
            if ($this->_authorizationModel->isAllowed('Magento_Sales::cancel') === false) {
                unset($argument['cancel_order']);
            }
            if ($this->_authorizationModel->isAllowed('Magento_Sales::hold') === false) {
                unset($argument['hold_order']);
            }
            if ($this->_authorizationModel->isAllowed('Magento_Sales::unhold') === false) {
                unset($argument['unhold_order']);
            }
            if ($this->_authorizationModel->isAllowed('Magento_SalesArchive::remove') === false) {
                unset($argument['remove_order_from_archive']);
            }
        }

        return $argument;
    }
}
