<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Controller\Adminhtml\Targetrule;

class Edit extends \Magento\TargetRule\Controller\Adminhtml\Targetrule
{
    /**
     * Edit action
     *
     * @return void
     */
    public function execute()
    {
        /* @var $model \Magento\TargetRule\Model\Rule */
        $model = $this->_objectManager->create(\Magento\TargetRule\Model\Rule::class);
        $ruleId = $this->getRequest()->getParam('id', null);

        if ($ruleId) {
            $model->load($ruleId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This rule no longer exists.'));
                $this->_redirect('adminhtml/*');
                return;
            }
        }

        $data = $this->_objectManager->get(\Magento\Backend\Model\Session::class)->getFormData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $this->_coreRegistry->register('current_target_rule', $model);

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Related Products Rule'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New Related Products Rule')
        );
        $this->_view->renderLayout();
    }
}
