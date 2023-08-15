<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Index;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class Send extends \Magento\GiftRegistry\Controller\Index
{
    /**
     * Share selected gift registry entity
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/*/share', ['_current' => true]);
        }

        try {
            /** @var $entity \Magento\GiftRegistry\Model\Entity */
            $entity = $this->_initEntity()->addData($this->getRequest()->getPostValue());

            $result = $entity->sendShareRegistryEmails();

            if ($result->getIsSuccess()) {
                $this->messageManager->addSuccess($result->getSuccessMessage());
            } else {
                $this->messageManager->addError($result->getErrorMessage());
                $this->_getSession()->setSharingForm($this->getRequest()->getPostValue());
                return $resultRedirect->setPath('*/*/share', ['_current' => true]);
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $message = __('Something went wrong while sending email(s).');
            $this->messageManager->addException($e, $message);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
