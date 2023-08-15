<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Controller class SaveNew. Responsible for save RMA request
 */
class SaveNew extends \Magento\Rma\Controller\Adminhtml\Rma implements HttpPostActionInterface
{
    /**
     * Process additional RMA information (like comment, customer notification etc)
     *
     * @param array $saveRequest
     * @param \Magento\Rma\Model\Rma $rma
     * @return \Magento\Rma\Controller\Adminhtml\Rma
     */
    protected function _processNewRmaAdditionalInfo(array $saveRequest, \Magento\Rma\Model\Rma $rma)
    {
        /** @var $statusHistory \Magento\Rma\Model\Rma\Status\History */
        $systemComment = $this->_objectManager->create(\Magento\Rma\Model\Rma\Status\History::class);
        $systemComment->setRmaEntityId($rma->getEntityId());
        if (isset($saveRequest['rma_confirmation']) && $saveRequest['rma_confirmation']) {
            try {
                $systemComment->sendNewRmaEmail();
            } catch (\Magento\Framework\Exception\MailException $exception) {
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($exception);
                $this->messageManager->addWarning(
                    __('You did not email your customer. Please check your email settings.')
                );
            }
        }
        $systemComment->saveSystemComment();
        if (!empty($saveRequest['comment']['comment'])) {
            $visible = isset($saveRequest['comment']['is_visible_on_front']);
            /** @var $statusHistory \Magento\Rma\Model\Rma\Status\History */
            $customComment = $this->_objectManager->create(\Magento\Rma\Model\Rma\Status\History::class);
            $customComment->setRmaEntityId($rma->getEntityId());
            $customComment->saveComment($saveRequest['comment']['comment'], $visible, true);
        }
        return $this;
    }
    /**
     * Save new RMA request
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost() || $this->getRequest()->getParam('back', false)) {
            $this->_redirect('adminhtml/*/');
            return;
        }
        try {
            /** @var $model \Magento\Rma\Model\Rma */
            $model = $this->_initModel();
            $saveRequest = $this->rmaDataMapper->filterRmaSaveRequest($this->getRequest()->getPostValue());
            $model->setData(
                $this->rmaDataMapper->prepareNewRmaInstanceData(
                    $saveRequest,
                    $this->_coreRegistry->registry('current_order')
                )
            );
            if (!$model->saveRma($saveRequest)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save this RMA.'));
            }
            $this->_processNewRmaAdditionalInfo($saveRequest, $model);
            $this->messageManager->addSuccess(__('You submitted the RMA request.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $errorKeys = $this->_objectManager->get(\Magento\Framework\Session\Generic::class)->getRmaErrorKeys();
            $controllerParams = ['order_id' => $this->_coreRegistry->registry('current_order')->getId()];
            if (!empty($errorKeys) && isset($errorKeys['tabs']) && $errorKeys['tabs'] == 'items_section') {
                $controllerParams['active_tab'] = 'items_section';
            }
            $this->_redirect('adminhtml/*/new', $controllerParams);
            return;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t save this RMA.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }
        $this->_redirect('adminhtml/*/');
    }
}
