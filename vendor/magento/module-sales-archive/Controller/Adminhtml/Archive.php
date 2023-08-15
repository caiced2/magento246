<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Controller\Adminhtml;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Archive controller
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class Archive extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_SalesArchive::orders';

    /**
     * @var \Magento\SalesArchive\Model\Archive
     */
    protected $_archiveModel;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\SalesArchive\Model\Archive $archiveModel
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\SalesArchive\Model\Archive $archiveModel,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->_archiveModel = $archiveModel;
        $this->_fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Render archive grid
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    protected function _renderGrid()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
    }

    /**
     * Declare headers and content file in response for file download
     *
     * @param string $type
     * @return ResponseInterface
     */
    protected function _export($type)
    {
        $fileName = 'orders_archive.' . $type;
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        /** @var \Magento\Backend\Block\Widget\Grid\ExportInterface $grid */
        $grid = $resultLayout->getLayout()->getChildBlock('sales.order.grid', 'grid.export');

        if ($type == 'csv') {
            return $this->_fileFactory->create($fileName, $grid->getCsvFile(), DirectoryList::VAR_DIR);
        } else {
            return $this->_fileFactory->create($fileName, $grid->getExcelFile($fileName), DirectoryList::VAR_DIR);
        }
    }
}
