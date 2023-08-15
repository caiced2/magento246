<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Adminhtml\Giftregistry;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftRegistry\Controller\Adminhtml\Giftregistry;

/**
 * Giftregistry save controller class
 */
class Save extends Giftregistry implements HttpPostActionInterface, HttpGetActionInterface
{
    /**
     * Filter post data
     *
     * @param array $data
     * @return array
     */
    protected function _filterPostData($data)
    {
        /* @var $filterManager \Magento\Framework\Filter\FilterManager */
        $filterManager = $this->_objectManager->get(\Magento\Framework\Filter\FilterManager::class);
        if (!empty($data['type']['label'])) {
            $data['type']['label'] = $filterManager->stripTags($data['type']['label']);
        }
        if (!empty($data['attributes']['registry'])) {
            foreach ($data['attributes']['registry'] as &$regItem) {
                if (!empty($regItem['label'])) {
                    $regItem['label'] = $filterManager->stripTags($regItem['label']);
                }
                if (!empty($regItem['options'])) {
                    foreach ($regItem['options'] as &$option) {
                        if (!isset($option['use_default'])) {
                            $option['label'] = $filterManager->stripTags($option['label']);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Save gift registry type
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            //filtering
            $data = $this->_filterPostData($data);
            try {
                $model = $this->_initType();
                $model->loadPost($data);
                $model->save();
                $this->messageManager->addSuccess(__('You saved the gift registry type.'));

                $redirectBack = $this->getRequest()->getParam('back', false);
                if ($redirectBack) {
                    $this->_redirect(
                        'adminhtml/*/edit',
                        ['id' => $model->getId(), 'store' => $model->getStoreId()]
                    );
                    return;
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('adminhtml/*/edit', ['id' => $model->getId()]);
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t save this gift registry type right now.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }
        $this->_redirect('adminhtml/*/');
    }
}
