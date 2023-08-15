<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

class Edit extends \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping
{
    /**
     * Edit gift wrapping
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $model = $this->_initModel();
        $resultPage = $this->initResultPage();
        $formData = $this->_objectManager->get(\Magento\Backend\Model\Session::class)->getFormData();
        if ($formData) {
            $model->addData($formData);
        }
        $resultPage->getConfig()->getTitle()->prepend(__('%1', $model->getDesign()));
        return $resultPage;
    }
}
