<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

/**
 * Gift Wrapping Controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Giftwrapping extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_GiftWrapping::magento_giftwrapping';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry)
    {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init active menu
     *
     * @return \Magento\Backend\Model\View\Result\Page
     * @codeCoverageIgnore
     */
    protected function initResultPage()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_GiftWrapping::sales_magento_giftwrapping');
        $resultPage->getConfig()->getTitle()->prepend(__('Gift Wrapping'));
        return $resultPage;
    }

    /**
     * Init model
     *
     * @param string $requestParam
     * @return \Magento\GiftWrapping\Model\Wrapping
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initModel($requestParam = 'id')
    {
        $model = $this->_coreRegistry->registry('current_giftwrapping_model');
        if ($model) {
            return $model;
        }
        $model = $this->_objectManager->create(\Magento\GiftWrapping\Model\Wrapping::class);
        $model->setStoreId($this->getRequest()->getParam('store', 0));

        $wrappingId = $this->getRequest()->getParam($requestParam);
        if ($wrappingId) {
            $model->load($wrappingId);
            if (!$model->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please request the correct gift wrapping.')
                );
            }
        }
        $this->_coreRegistry->register('current_giftwrapping_model', $model);

        return $model;
    }

    /**
     * Prepare Gift Wrapping Raw data
     *
     * @param array $wrappingRawData
     * @return array
     */
    protected function _prepareGiftWrappingRawData($wrappingRawData)
    {
        unset($wrappingRawData['tmp_image']);
        if (isset($wrappingRawData['image_name']['value'])) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $wrappingRawData['image_name']['value'] = basename($wrappingRawData['image_name']['value']);
        }
        return $wrappingRawData;
    }
}
