<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Class Save. Represents request logic for saving new order's return
 */
class Save extends SaveNew implements HttpPostActionInterface
{
    /**
     * Save RMA request
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $rmaId = (int)$this->getRequest()->getParam('rma_id');
        if (!$rmaId) {
            parent::execute();
            return;
        }
        try {
            $saveRequest = $this->rmaDataMapper->filterRmaSaveRequest($this->getRequest()->getPostValue());
            $itemStatuses = $this->rmaDataMapper->combineItemStatuses($saveRequest['items'], $rmaId);
            $model = $this->_initModel('rma_id');
            /** @var $sourceStatus \Magento\Rma\Model\Rma\Source\Status */
            $sourceStatus = $this->_objectManager->create(\Magento\Rma\Model\Rma\Source\Status::class);
            $model->setStatus($sourceStatus->getStatusByItems($itemStatuses))->setIsUpdate(1);
            if (!$model->saveRma($saveRequest)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save this RMA.'));
            }
            /** @var $statusHistory \Magento\Rma\Model\Rma\Status\History */
            $statusHistory = $this->_objectManager->create(\Magento\Rma\Model\Rma\Status\History::class);
            $statusHistory->setRmaEntityId($model->getEntityId());
            if ($model->getIsSendAuthEmail()) {
                $statusHistory->sendAuthorizeEmail();
            }
            if ($model->getStatus() !== $model->getOrigData('status')) {
                $statusHistory->saveSystemComment();
            }
            $this->messageManager->addSuccess(__('You saved the RMA request.'));
            $redirectBack = $this->getRequest()->getParam('back', false);
            if ($redirectBack) {
                $this->_redirect('adminhtml/*/edit', ['id' => $rmaId, 'store' => $model->getStoreId()]);
                return;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $errorKeys = $this->_objectManager->get(\Magento\Framework\Session\Generic::class)->getRmaErrorKeys();
            $controllerParams = ['id' => $rmaId];
            if (isset($errorKeys['tabs']) && $errorKeys['tabs'] == 'items_section') {
                $controllerParams['active_tab'] = 'items_section';
            }
            $this->_redirect('adminhtml/*/edit', $controllerParams);
            return;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t save this RMA.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->_redirect('adminhtml/*/');
            return;
        }
        $this->_redirect('adminhtml/*/');
    }
}
