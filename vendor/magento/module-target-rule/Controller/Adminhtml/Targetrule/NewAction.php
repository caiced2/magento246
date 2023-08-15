<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Controller\Adminhtml\Targetrule;

class NewAction extends \Magento\TargetRule\Controller\Adminhtml\Targetrule
{
    /**
     * Create new target rule
     *
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
