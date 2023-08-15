<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\SalesArchive\Model\Archive;
use Magento\SalesArchive\Model\Config;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Mass action to move orders to archive from order grid
 */
class MassAdd extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_SalesArchive::add';

    /**
     * @var \Magento\SalesArchive\Model\Archive
     */
    protected $_archiveModel;

    /**
     * @var Config|null
     */
    private $_config;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param Archive $archiveModel
     * @param CollectionFactory $collectionFactory
     * @param Config|null $config
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
     * Add selected orders to archive
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        if ($this->_config->isArchiveActive()) {
            $archivedIds = $this->_archiveModel->archiveOrdersById($collection->getAllIds());
            $archivedCount = count($archivedIds);

            if ($archivedCount > 0) {
                $this->messageManager->addSuccess(__('We archived %1 order(s).', $archivedCount));
            } else {
                $this->messageManager->addWarning(__("We can't archive the selected order(s)."));
            }
        } else {
            $this->messageManager->addWarning(__("We can't archive the selected order(s)."));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('sales/order/');

        return $resultRedirect;
    }
}
