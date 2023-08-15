<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Controller\Adminhtml\Reminder;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Reminder\Controller\Adminhtml\Reminder;
use Magento\Rule\Model\Condition\ConditionInterface;

class NewConditionHtml extends Reminder implements HttpPostActionInterface
{
    /**
     * Add new condition
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        if (class_exists($type) && !in_array(ConditionInterface::class, class_implements($type))) {
            $html = '';
            $this->getResponse()->setBody($html);
            return;
        }

        $model = $this->_conditionFactory->create(
            $type
        )->setId(
            $id
        )->setType(
            $type
        )->setRule(
            $this->_ruleFactory->create()
        )->setPrefix(
            'conditions'
        );
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof \Magento\Rule\Model\Condition\AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }
}
