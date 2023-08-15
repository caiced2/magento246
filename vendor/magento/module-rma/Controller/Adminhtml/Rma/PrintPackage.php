<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\App\Filesystem\DirectoryList;

class PrintPackage extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Create pdf document with information about packages
     *
     * @return void|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $model = $this->_initModel();
        /** @var $shippingModel \Magento\Rma\Model\Shipping */
        $shippingModel = $this->_objectManager->create(\Magento\Rma\Model\Shipping::class);
        $shipment = $shippingModel->getShippingLabelByRma($model);

        if ($shipment) {
            /** @var $orderPdf \Magento\Shipping\Model\Order\Pdf\Packaging */
            $orderPdf = $this->_objectManager->create(\Magento\Shipping\Model\Order\Pdf\Packaging::class);
            /** @var $block \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod */
            $block = $this->_view->getLayout()->getBlockSingleton(
                \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod::class
            );
            $orderPdf->setPackageShippingBlock($block);
            $pdf = $orderPdf->getPdf($shipment);
            /** @var $dateModel \Magento\Framework\Stdlib\DateTime\DateTime */
            $dateModel = $this->_objectManager->get(\Magento\Framework\Stdlib\DateTime\DateTime::class);
            return $this->_fileFactory->create(
                'packingslip' . $dateModel->date('Y-m-d_H-i-s') . '.pdf',
                $pdf->render(),
                DirectoryList::MEDIA,
                'application/pdf'
            );
        } else {
            $this->_forward('noroute');
        }
    }
}
