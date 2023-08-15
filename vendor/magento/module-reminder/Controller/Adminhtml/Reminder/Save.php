<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Controller\Adminhtml\Reminder;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterInput;
use Magento\Reminder\Controller\Adminhtml\Reminder;
use Psr\Log\LoggerInterface;

/**
 * Reminder rule save controller
 */
class Save extends Reminder implements HttpPostActionInterface
{
    /**
     * Save reminder rule
     *
     * @return void
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getPostValue()) {
            try {
                $redirectBack = $this->getRequest()->getParam('back', false);

                $model = $this->_initRule('rule_id');

                if ($data['from_date']) {
                    $inputFilter = new FilterInput(['from_date' => $this->_dateFilter], [], $data);
                    $data = $inputFilter->getUnescaped();
                    $data['from_date'] = $this->timeZoneResolver->convertConfigTimeToUtc($data['from_date']);
                }

                if ($data['to_date']) {
                    $inputFilter = new FilterInput(['to_date' => $this->_dateFilter], [], $data);
                    $data = $inputFilter->getUnescaped();
                    $data['to_date'] = $this->timeZoneResolver->convertConfigTimeToUtc($data['to_date']);
                }

                $validateResult = $model->validateData(new DataObject($data));
                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                    $this->_getSession()->setFormData($data);

                    $this->_redirect('adminhtml/*/edit', ['id' => $model->getId()]);
                    return;
                }

                $data['conditions'] = $data['rule']['conditions'];
                // @phpstan-ignore-next-line
                unset($data['rule']);
                // @phpstan-ignore-next-line
                unset($data['conditions_serialized']);
                // @phpstan-ignore-next-line
                unset($data['actions_serialized']);

                $model->loadPost($data);
                $this->_getSession()->setPageData($model->getData());
                $model->save();

                $this->messageManager->addSuccess(__('You saved the reminder rule.'));
                $this->_getSession()->setPageData(false);

                if ($redirectBack) {
                    $this->_redirect('adminhtml/*/edit', ['id' => $model->getId(), '_current' => true]);
                    return;
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_getSession()->setPageData($data);
                // @phpstan-ignore-next-line
                $this->_redirect('adminhtml/*/edit', ['id' => $model->getId()]);
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We could not save the reminder rule.'));
                $this->_objectManager->get(LoggerInterface::class)->critical($e);
            }
        }
        $this->_redirect('adminhtml/*/');
    }
}
