<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Adminhtml\Giftregistry;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

/**
 * @codeCoverageIgnore
 */
class Index extends \Magento\GiftRegistry\Controller\Adminhtml\Giftregistry implements HttpGetActionInterface
{
    /**
     * Default action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $this->_view->renderLayout();
    }
}
