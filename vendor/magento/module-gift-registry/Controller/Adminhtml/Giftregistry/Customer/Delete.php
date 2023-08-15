<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Adminhtml\Giftregistry\Customer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class Delete extends \Magento\GiftRegistry\Controller\Adminhtml\Giftregistry\Customer
{
    /**
     * Delete gift registry action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $model = $this->_initEntity();
            $customerId = $model->getCustomerId();
            $model->delete();
            $this->messageManager->addSuccess(__('You deleted this gift registry entity.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            return $resultRedirect->setPath('adminhtml/*/edit', ['id' => $model->getId()]);
        } catch (\Exception $e) {
            $this->messageManager->addError(__("We couldn't delete this gift registry entity."));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }

        return $resultRedirect->setPath('customer/index/edit', ['id' => $customerId, 'active_tab' => 'giftregistry']);
    }
}
