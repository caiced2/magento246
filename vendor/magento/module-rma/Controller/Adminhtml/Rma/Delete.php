<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class Delete extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Delete rma
     *
     * @return void
     */
    public function execute()
    {
        $this->_redirect('adminhtml/*/');
    }
}
