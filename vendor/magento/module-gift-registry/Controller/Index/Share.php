<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Index;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class Share extends \Magento\GiftRegistry\Controller\Index
{
    /**
     * Share selected gift registry entity
     *
     * @return void|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $entity = $this->_initEntity();
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Share Gift Registry'));
            $this->_view->getLayout()->getBlock('giftregistry.customer.share')->setEntity($entity);
            $this->_view->renderLayout();
            return;
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $message = __('Something went wrong while sharing the gift registry.');
            $this->messageManager->addException($e, $message);
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
