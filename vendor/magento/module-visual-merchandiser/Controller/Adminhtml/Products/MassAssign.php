<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Controller\Adminhtml\Products;

use Magento\Framework\App\Action\HttpPostActionInterface;

class MassAssign extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::categories';

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $productIds = [];
        $notValidSkus = [];
        $response = $this->_objectManager->create(\Magento\Framework\DataObject::class);
        $response->setError(false);

        $layout = $this->layoutFactory->create();

        $sku = $this->_request->getParam('add_product_sku');
        $action = $this->_request->getParam('action');
        if (trim($sku) == "") {
            $this->messageManager->addErrorMessage(__('No SKU entered'));
            $response->setError(true);
        } else {
            $skus = preg_split('/\n|\r\n?/', $sku);

            foreach ($skus as $skuItem) {
                if (strlen(trim($skuItem)) > 0) {
                    try {
                        $productIds[] = $this->productRepository->get(trim($skuItem))->getId();
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $notValidSkus[] = trim($skuItem);
                    }
                }
            }

            if ($action != 'assign' && $action != 'remove') {
                $this->messageManager->addErrorMessage(__('Undefined Action'));
                $response->setError(true);
            }
        }

        if (!$this->messageManager->hasMessages()) {
            if (!empty($notValidSkus)) {
                $this->messageManager->addErrorMessage(
                    sprintf(
                        __("Products with the following SKUs do not exist: %s")->render(),
                        implode(', ', $notValidSkus)
                    )
                );
            }

            if (!empty($productIds)) {
                $this->messageManager->addSuccessMessage(
                    sprintf(__("%s SKU(s) processed successfully")->render(), count($productIds))
                );
                $response->setAction($action);
                $response->setIds($productIds);
            }
        }
        $layout->initMessages();
        $response->setHtmlMessage($layout->getMessagesBlock()->getGroupedHtml());
        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
