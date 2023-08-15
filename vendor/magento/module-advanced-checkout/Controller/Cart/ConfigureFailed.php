<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Cart;

use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\View\Result\PageFactory;

class ConfigureFailed extends \Magento\AdvancedCheckout\Controller\Cart
{
    /**
     * @var \Magento\Catalog\Helper\Product\View
     */
    protected $viewHelper;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @codeCoverageIgnoreStart
     * @param ActionContext $context
     * @param \Magento\Catalog\Helper\Product\View $viewHelper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        ActionContext $context,
        \Magento\Catalog\Helper\Product\View $viewHelper,
        PageFactory $resultPageFactory
    ) {
        $this->viewHelper = $viewHelper;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Configure failed item options
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $qty = $this->getRequest()->getParam('qty', 1);

        try {
            $params = new \Magento\Framework\DataObject();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            $buyRequest = new \Magento\Framework\DataObject(['product' => $id, 'qty' => $qty]);
            $params->setBuyRequest($buyRequest);
            $params->setBeforeHandles(['catalog_product_view']);
            $page = $this->resultPageFactory->create();
            $this->viewHelper->prepareAndRender($page, $id, $this, $params);
            return $page;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*');
        } catch (\Exception $e) {
            $this->messageManager->addError(__('You cannot configure a product.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*');
        }
    }
}
