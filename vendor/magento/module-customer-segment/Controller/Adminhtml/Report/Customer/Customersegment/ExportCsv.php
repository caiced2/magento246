<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;

class ExportCsv extends \Magento\CustomerSegment\Controller\Adminhtml\Report\Customer\Customersegment
{
    /**
     * Export Csv Action
     *
     * @return ResponseInterface|void
     */
    public function execute()
    {
        if ($this->_initSegment()) {
            $this->_view->loadLayout();
            $fileName = 'customersegment_customers.csv';
            $content = $this->_view->getLayout()->getChildBlock('report.customersegment.detail.grid', 'grid.export');
            return $this->_fileFactory->create(
                $fileName,
                $content->getCsvFile($fileName),
                DirectoryList::VAR_DIR
            );
        } else {
            $this->_redirect('*/*/detail', ['_current' => true]);
            return;
        }
    }
}
