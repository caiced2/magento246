<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Rule\Model\Condition\ConditionInterface;

class NewConditionHtml extends Index implements HttpPostActionInterface
{
    /**
     * Add new condition
     *
     * @return void
     */
    public function execute()
    {
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        if (class_exists($type) && !in_array(ConditionInterface::class, class_implements($type))) {
            $html = '';
            $this->getResponse()->setBody($html);
            return;
        }

        $id = $this->getRequest()->getParam('id');

        $segment = $this->_objectManager->create(\Magento\CustomerSegment\Model\Segment::class);
        $segment->setApplyTo((int)$this->getRequest()->getParam('apply_to'));

        $model = $this->_conditionFactory->create(
            $type
        )->setId(
            $id
        )->setType(
            $type
        )->setRule(
            $segment
        )->setPrefix(
            'conditions'
        );
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        $html = '';
        if ($model instanceof \Magento\Rule\Model\Condition\AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        }
        $this->getResponse()->setBody($html);
    }
}
