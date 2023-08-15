<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Adminhtml\Reward\Rate;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

/**
 * @codeCoverageIgnore
 */
class Index extends \Magento\Reward\Controller\Adminhtml\Reward\Rate implements HttpGetActionInterface
{
    /**
     * Index Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Reward Exchange Rates'));
        $this->_view->renderLayout();
    }
}
