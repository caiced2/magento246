<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Tracking;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Rma\Helper\Data as Helper;

/**
 * Class to print Rma packages
 */
class PackagePrint extends \Magento\Rma\Controller\Tracking implements HttpGetActionInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileResponseFactory
     * @param Helper $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileResponseFactory,
        Helper $helper
    ) {
        parent::__construct($context, $coreRegistry, $fileResponseFactory);
        $this->helper = $helper;
    }

    /**
     * Create pdf document with information about packages
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->helper->decodeTrackingHash($this->getRequest()->getParam('hash'));
        if ($data['key'] == 'rma_id') {
            $this->_loadValidRma($data['id']);
        }

        /** @var $shippingInfoModel \Magento\Rma\Model\Shipping\Info */
        $shippingInfoModel = $this->_objectManager->create(\Magento\Rma\Model\Shipping\Info::class);
        $shippingInfoModel->loadPackage($this->getRequest()->getParam('hash'));
        if ($shippingInfoModel) {
            /** @var $orderPdf \Magento\Shipping\Model\Order\Pdf\Packaging */
            $orderPdf = $this->_objectManager->create(\Magento\Shipping\Model\Order\Pdf\Packaging::class);
            $block = $this->_view->getLayout()->getBlockSingleton(
                \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod::class
            );
            $orderPdf->setPackageShippingBlock($block);
            $pdf = $orderPdf->getPdf($shippingInfoModel);
            /** @var $dateModel \Magento\Framework\Stdlib\DateTime\DateTime */
            $dateModel = $this->_objectManager->get(\Magento\Framework\Stdlib\DateTime\DateTime::class);
            $this->_fileResponseFactory->create(
                'packingslip' . $dateModel->date('Y-m-d_H-i-s') . '.pdf',
                $pdf->render(),
                DirectoryList::VAR_DIR,
                'application/pdf'
            );
        }
    }
}
