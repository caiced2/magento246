<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Index;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class Items extends \Magento\GiftRegistry\Controller\Index
{
    /**
     * View items of selected gift registry entity
     *
     * @return void|\Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        try {
            $this->_coreRegistry->register('current_entity', $this->_initEntity());
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Gift Registry Items'));
            $this->_view->renderLayout();
            return;
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
