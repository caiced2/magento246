<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Controller\Adminhtml\Targetrule;

use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Target rule save controller
 */
class Save extends \Magento\TargetRule\Controller\Adminhtml\Targetrule implements HttpPostActionInterface
{
    /**
     * Save action
     *
     * @return void
     */
    public function execute()
    {
        $redirectPath = '*/*/';
        $redirectParams = [];

        $data = $this->getRequest()->getPostValue();

        if ($this->getRequest()->isPost() && $data) {
            /* @var $model \Magento\TargetRule\Model\Rule */
            $model = $this->_objectManager->create(\Magento\TargetRule\Model\Rule::class);

            try {
                $data = $this->filterDates($data);

                $ruleId = $this->getRequest()->getParam('rule_id');
                $errors = $this->validateData($data, $model, $ruleId);

                if (empty($errors)) {
                    $data['conditions'] = $data['rule']['conditions'];
                    $data['actions'] = $data['rule']['actions'];
                    unset($data['rule']);
                    unset($data['conditions_serialized']);
                    unset($data['actions_serialized']);

                    $model->loadPost($data);
                    $model->save();

                    $this->messageManager->addSuccess(__('You saved the rule.'));

                    if ($this->getRequest()->getParam('back', false)) {
                        $redirectPath = 'adminhtml/*/edit';
                        $redirectParams = ['id' => $model->getId(), '_current' => true];
                    }
                }
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t save the product rule right now.'));
                $errors[] = $e->getMessage();
            }
            if (!empty($errors)) {
                foreach ($errors as $errorMessage) {
                    $this->messageManager->addError($errorMessage);
                }
                $this->_getSession()->setFormData($data);
                $redirectPath = 'adminhtml/*/edit';
                $redirectParams = ['id' => $this->getRequest()->getParam('rule_id')];
            }
        }
        $this->_redirect($redirectPath, $redirectParams);
    }

    /**
     * Validate data
     *
     * @param array $data
     * @param \Magento\TargetRule\Model\Rule $model
     * @param int $ruleId
     *
     * @return array
     */
    public function validateData($data, $model, $ruleId)
    {
        $errors = [];

        if ($ruleId) {
            $model->load($ruleId);
            if ($ruleId != $model->getId()) {
                $errors[] = __('Please specify a correct rule.')->getText();
                return $errors;
            }
        }
        $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
        if ($validateResult !== true) {
            foreach ($validateResult as $errorMessage) {
                $errors[] = $errorMessage;
            }
        }
        return $errors;
    }

    /**
     * Validating and filtering dates
     *
     * @param array $data
     * @return array
     */
    private function filterDates(array $data)
    {
        if ($data['from_date']) {
            $data['from_date'] = $this->_dateFilter->filter($data['from_date']);
        }
        if ($data['to_date']) {
            $data['to_date'] = $this->_dateFilter->filter($data['to_date']);
        }
        return $data;
    }
}
