<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Adminhtml\Reward\Rate;

/**
 * @codeCoverageIgnore
 */
class NewAction extends \Magento\Reward\Controller\Adminhtml\Reward\Rate
{
    /**
     * New Action.
     * Forward to Edit Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
