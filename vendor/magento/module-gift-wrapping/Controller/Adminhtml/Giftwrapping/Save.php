<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping
{
    /**
     * Save gift wrapping
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $wrappingRawData = $this->_prepareGiftWrappingRawData($this->getRequest()->getPost('wrapping'));
        if ($wrappingRawData) {
            try {
                $model = $this->_initModel();
                $model->addData($wrappingRawData);

                $data = new \Magento\Framework\DataObject($wrappingRawData);
                if ($data->getData('image_name/delete')) {
                    $model->setImage('');
                    // Delete temporary image if exists
                    $model->unsTmpImage();
                } else {
                    try {
                        $model->attachUploadedImage('image_name');
                    } catch (\Exception $e) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('You have not uploaded the image.')
                        );
                    }
                }

                $model->save();
                $this->messageManager->addSuccess(__('You saved the gift wrapping.'));

                $redirectBack = $this->getRequest()->getParam('back', false);
                if ($redirectBack) {
                    $resultRedirect->setPath(
                        'adminhtml/*/edit',
                        ['id' => $model->getId(), 'store' => $model->getStoreId()]
                    );
                    return $resultRedirect;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $resultRedirect->setPath('adminhtml/*/edit', ['id' => $model->getId()]);
                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t save the gift wrapping right now.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }
        $resultRedirect->setPath('adminhtml/*/');
        return $resultRedirect;
    }
}
