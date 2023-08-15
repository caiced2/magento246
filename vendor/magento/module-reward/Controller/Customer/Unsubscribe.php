<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;

class Unsubscribe extends \Magento\Reward\Controller\Customer
{
    /**
     * Unsubscribe customer from update/warning balance notifications
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $notification = $this->getRequest()->getParam('notification');
        if (!in_array($notification, ['update', 'warning'])) {
            $this->_forward('noroute');
        }

        try {
            /* @var $customer \Magento\Customer\Model\Session */
            $customer = $this->_getCustomer();
            if ($customer->getId()) {
                if ($notification == 'update') {
                    $customer->setRewardUpdateNotification(false);
                    $customer->getResource()->saveAttribute($customer, 'reward_update_notification');
                } elseif ($notification == 'warning') {
                    $customer->setRewardWarningNotification(false);
                    $customer->getResource()->saveAttribute($customer, 'reward_warning_notification');
                }
                $this->messageManager->addSuccess(__('You unsubscribed.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__('You can\'t unsubscribed right now.'));
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/info');
    }
}
