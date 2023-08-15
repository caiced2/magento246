<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Controller\Adminhtml\Banner;

class Delete extends \Magento\Banner\Controller\Adminhtml\Banner
{
    /**
     * Delete action
     *
     * @return void
     */
    public function execute()
    {
        // check if we know what should be deleted
        $bannerId = $this->getRequest()->getParam('id');
        if ($bannerId) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(\Magento\Banner\Model\Banner::class);
                $model->load($bannerId);
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__('You deleted the dynamic block.'));
                // go to grid
                $this->_redirect('adminhtml/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __(
                        'Something went wrong while deleting dynamic block data. '
                        . 'Please review the action log and try again.'
                    )
                );
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                // save data in session
                $this->_getSession()->setFormData($this->getRequest()->getParams());
                // redirect to edit form
                $this->_redirect('adminhtml/*/edit', ['id' => $bannerId]);
                return;
            }
        }
        // display error message
        $this->messageManager->addError(__('We cannot find a dynamic block to delete.'));
        // go to grid
        $this->_redirect('adminhtml/*/');
    }
}
