<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\Framework\Controller\ResultFactory;

class ChangeStatus extends \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping
{
    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping
     */
    protected $wrappingResource;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping $wrappingModelResource
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping $wrappingModelResource
    ) {
        $this->wrappingResource = $wrappingModelResource;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Change gift wrapping(s) status action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $wrappingIds = (array)$this->getRequest()->getParam('wrapping_ids');
        $status = (int)(bool)$this->getRequest()->getParam('status');
        try {
            $this->wrappingResource->updateStatus($status, $wrappingIds);
            $this->messageManager->addSuccess(__('You updated a total of %1 records.', count($wrappingIds)));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong while updating the wrapping(s) status.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('adminhtml/*/index');
    }
}
