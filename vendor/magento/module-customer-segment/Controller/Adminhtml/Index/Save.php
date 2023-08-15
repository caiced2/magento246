<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * CustomerSegment save controller
 */
class Save extends \Magento\CustomerSegment\Controller\Adminhtml\Index implements HttpPostActionInterface
{
    /**
     * Save customer segment
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            try {
                $redirectBack = $this->getRequest()->getParam('back', false);

                $model = $this->_initSegment('segment_id');

                // Sanitize apply_to property
                if (array_key_exists('apply_to', $data)) {
                    $data['apply_to'] = (int)$data['apply_to'];
                }

                $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                    $this->_getSession()->setFormData($data);

                    $this->_redirect('customersegment/*/edit', ['id' => $model->getId()]);
                    return;
                }

                if (array_key_exists('rule', $data)) {
                    $data['conditions'] = $data['rule']['conditions'];
                    unset($data['rule']);
                }

                unset($data['conditions_serialized']);
                unset($data['actions_serialized']);

                $model->loadPost($data);
                $this->_session->setPageData($model->getData());
                $model->save();
                if ($model->getApplyTo() != \Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS) {
                    $model->matchCustomers();
                }

                $this->messageManager->addSuccess(
                    __('You saved the segment. Customers matching is added to the messages queue.')
                );
                $this->_session->setPageData(false);

                if ($redirectBack) {
                    $this->_redirect('customersegment/*/edit', ['id' => $model->getId(), '_current' => true]);
                    return;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setPageData($data);
                $this->_redirect('customersegment/*/edit', ['id' => $this->getRequest()->getParam('segment_id')]);
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t save the segment right now.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }
        $this->_redirect('customersegment/*/');
    }
}
