<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Gift Wrapping Delete
 */
class Delete extends \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping implements HttpPostActionInterface
{
    /**
     * Delete current gift wrapping
     *
     * This action can be performed on 'Edit Gift Wrapping' page
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $wrapping = $this->_objectManager->create(\Magento\GiftWrapping\Model\Wrapping::class);
        $wrapping->load($this->getRequest()->getParam('id', false));
        if ($wrapping->getId()) {
            try {
                $wrapping->delete();
                $this->messageManager->addSuccess(__('You deleted the gift wrapping.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
                return $resultRedirect;
            }
        } else {
            $errorMessage = 'Requested gift wrapping does not exist.';
            $this->messageManager->addErrorMessage(__($errorMessage));
        }
        $resultRedirect->setPath('adminhtml/*/');
        return $resultRedirect;
    }
}
