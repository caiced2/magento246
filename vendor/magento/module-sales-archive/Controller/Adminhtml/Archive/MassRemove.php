<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\SalesArchive\Model\Archive;
use Magento\SalesArchive\Model\Config;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Mass action to remove orders from archive
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class MassRemove extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_SalesArchive::remove';

    /**
     * @var \Magento\SalesArchive\Model\Archive
     */
    protected $_archiveModel;

    /**
     * @var Config|null
     */
    private $_config;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param Filter $filter
     * @param \Magento\SalesArchive\Model\Archive $archiveModel
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        \Magento\SalesArchive\Model\Archive $archiveModel,
        CollectionFactory $collectionFactory,
        \Magento\SalesArchive\Model\Config $config = null
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->_archiveModel = $archiveModel;
        $this->_config = $config ?? ObjectManager::getInstance()->get(Config::class);
        parent::__construct($context, $filter);
    }

    /**
     * Remove selected orders from archive
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        if ($this->_config->isArchiveActive()) {
            $archivedIds = $this->_archiveModel->removeOrdersFromArchiveById($collection->getAllIds());
            $archivedCount = count($archivedIds);

            if ($archivedCount > 0) {
                $this->messageManager->addSuccess(__('We removed %1 order(s) from the archive.', $archivedCount));
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('sales/archive/orders');

        return $resultRedirect;
    }
}
