<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class Add extends \Magento\SalesArchive\Controller\Adminhtml\Archive
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_SalesArchive::add';

    /**
     * Archive order action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($orderId) {
            $archivedOrderIds = $this->_archiveModel->archiveOrdersById($orderId);
            if (count($archivedOrderIds)) {
                $this->messageManager->addSuccess(__('We have archived the order.'));
            } else {
                $this->messageManager->addError(__('We could not archive the order. Please try again later.'));
            }
            $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
        } else {
            $this->messageManager->addError(__('Please specify the order ID to be archived.'));
            $resultRedirect->setPath('sales/order');
        }

        return $resultRedirect;
    }
}
