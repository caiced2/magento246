<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount;

class Edit extends \Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount
{
    /**
     * Edit GiftCardAccount
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_initGca();

        if (!$model->getId() && $id) {
            $this->messageManager->addError(__('This gift card account has been deleted.'));
            $this->_redirect('adminhtml/*/');
            return;
        }

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $this->_view->loadLayout();

        $this->_addBreadcrumb(
            $id ? __('Edit Gift Card Account') : __('New Gift Card Account'),
            $id ? __('Edit Gift Card Account') : __('New Gift Card Account')
        );
        $this->_addContent(
            $this->_view->getLayout()->createBlock(
                \Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit::class
            )->setData(
                'form_action_url',
                $this->getUrl('adminhtml/*/save')
            )
        )->_addLeft(
            $this->_view->getLayout()->createBlock(
                \Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit\Tabs::class
            )
        )->_setActiveMenu(
            'Magento_GiftCardAccount::customer_giftcardaccount'
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Gift Card Accounts'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getCode() : __('New Account')
        );
        $this->_view->renderLayout();
    }
}
