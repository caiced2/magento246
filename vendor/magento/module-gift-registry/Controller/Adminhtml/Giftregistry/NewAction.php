<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Adminhtml\Giftregistry;

use Magento\Framework\Exception\LocalizedException;

class NewAction extends \Magento\GiftRegistry\Controller\Adminhtml\Giftregistry
{
    /**
     * Create new gift registry type
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function execute()
    {
        try {
            $model = $this->_initType();
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('adminhtml/*/');
            return;
        }

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('New Gift Registry Type'));

        $block = $this->_view->getLayout()->createBlock(
            \Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit::class
        )->setData(
            'form_action_url',
            $this->getUrl('adminhtml/*/save')
        );

        $this->_addBreadcrumb(
            __('New Type'),
            __('New Type')
        )->_addContent(
            $block
        )->_addLeft(
            $this->_view->getLayout()->createBlock(\Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Tabs::class)
        );
        $this->_view->renderLayout();
    }
}
