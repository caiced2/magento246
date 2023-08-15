<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Controller\Adminhtml\Update;

use Magento\Framework\Controller\ResultInterface;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Staging::staging';

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $massActionFilter;

    /**
     * @var \Magento\Staging\Model\ResourceModel\Update\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Ui\Component\MassAction\Filter $massActionFilter
     * @param \Magento\Staging\Model\ResourceModel\Update\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Ui\Component\MassAction\Filter $massActionFilter,
        \Magento\Staging\Model\ResourceModel\Update\CollectionFactory $collectionFactory
    ) {
        $this->updateRepository = $updateRepository;
        $this->massActionFilter = $massActionFilter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $count = 0;
        try {
            $collection = $this->massActionFilter->getCollection($this->collectionFactory->create());
            foreach ($collection as $update) {
                $this->updateRepository->delete($update);
                ++$count;
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        if ($count) {
            $this->messageManager->addSuccess(sprintf('You deleted %d update(s).', $count));
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Staging::staging');
    }
}
