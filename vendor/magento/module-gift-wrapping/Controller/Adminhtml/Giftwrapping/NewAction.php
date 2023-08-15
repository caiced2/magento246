<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

/**
 * @codeCoverageIgnore
 */
class NewAction extends \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping
{
    /**
     * Create new gift wrapping
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $this->_initModel();
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('New Gift Wrapping'));
        return $resultPage;
    }
}
