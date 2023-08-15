<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Controller\Adminhtml\Update;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends \Magento\Backend\App\Action
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
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
    ) {
        $this->updateRepository = $updateRepository;
        parent::__construct($context);
    }

    /**
     * Updates grid
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $updateId = (int)$this->getRequest()->getParam('id');

        if (!$updateId) {
            return $this->prepareResultRedirect();
        }

        try {
            $update = $this->updateRepository->get($updateId);
            $title = $update->getName();
        } catch (NoSuchEntityException $e) {
            return $this->prepareResultRedirect();
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Staging::staging');
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function prepareResultRedirect()
    {
        $this->messageManager->addErrorMessage(__('This update not exists.'));
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('staging/update');
    }
}
