<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Adminhtml\Customer\Reward;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Reward\Controller\Adminhtml\Customer\Reward as RewardAction;

/**
 * Used for accordion on a customer page, requires POST because of the accordion mechanism.
 *
 * @codeCoverageIgnore
 */
class History extends RewardAction implements HttpPostActionInterface
{
    /**
     * History Ajax Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
