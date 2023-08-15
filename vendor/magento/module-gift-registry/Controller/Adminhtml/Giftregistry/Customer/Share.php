<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Controller\Adminhtml\Giftregistry\Customer;

use Magento\Framework\Exception\LocalizedException;

class Share extends \Magento\GiftRegistry\Controller\Adminhtml\Giftregistry\Customer
{
    /**
     * Share gift registry action
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $model = $this->_initEntity();
        $data = $this->getRequest()->getParam('emails');
        if ($data) {
            $emails = explode(',', $data);
            $emailsForSend = [];

            if ($this->_storeManager->hasSingleStore()) {
                $storeId = $this->_storeManager->getStore(true)->getId();
            } else {
                $storeId = $this->getRequest()->getParam('store_id');
            }
            $model->setStoreId($storeId);

            try {
                $sentCount = 0;
                $failedCount = 0;
                foreach ($emails as $email) {
                    if (!empty($email)) {
                        if ($model->sendShareRegistryEmail($email, $storeId, $this->getRequest()->getParam('message'))
                        ) {
                            $sentCount++;
                        } else {
                            $failedCount++;
                        }
                        $emailsForSend[] = $email;
                    }
                }
                if (empty($emailsForSend)) {
                    throw new LocalizedException(
                        __('At least one email address is needed. Enter an email address and try again.')
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            }

            if ($sentCount) {
                $this->messageManager->addSuccess(__('%1 email(s) were sent.', $sentCount));
            }
            if ($failedCount) {
                $this->messageManager->addError(
                    __("We couldn't send '%1 of %2 emails.", $failedCount, count($emailsForSend))
                );
            }
        }
        $this->_redirect('adminhtml/*/edit', ['id' => $model->getId()]);
    }
}
