<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * CSV file with SKUs and quantity upload controller.
 */
class UploadSkuCsv extends \Magento\AdvancedCheckout\Controller\Adminhtml\Index implements HttpPostActionInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CustomerInterfaceFactory $customerFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CustomerInterfaceFactory $customerFactory,
        DataObjectHelper $dataObjectHelper,
        ?StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $registry, $customerFactory, $dataObjectHelper);
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Upload and parse CSV file with SKUs and quantity
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        try {
            $this->_initData();
        } catch (LocalizedException $e) {
            $this->_objectManager->get(LoggerInterface::class)->critical($e);
            $this->_redirect('customer/index');
            $this->_redirectFlag = true;
        }
        if ($this->_redirectFlag) {
            return;
        }

        /** @var $helper Data */
        $helper = $this->_objectManager->get(Data::class);

        $rows = [];
        if ($helper->isSkuFileUploaded($this->getRequest())) {
            $rows = $helper->processSkuFileUploading() ?: [];
        }

        $items = $this->getRequest()->getPost('add_by_sku');
        if (!is_array($items)) {
            $items = [];
        }
        $result = [];
        foreach ($items as $sku => $qty) {
            $result[] = ['sku' => $sku, 'qty' => $qty['qty']];
        }
        foreach ($rows as $row) {
            $result[] = $row;
        }

        if (!empty($result)) {
            $cart = $this->getCartModel();
            $cart->prepareAddProductsBySku($result);
            $cart->saveAffectedProducts($this->getCartModel(), true);
        }
        $this->storeManager->setCurrentStore(Store::ADMIN_CODE);
        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
    }
}
