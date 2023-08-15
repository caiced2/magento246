<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Formtype;

class Create extends \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Formtype
{
    /**
     * Create new form type from skeleton
     *
     * @return void
     */
    public function execute()
    {
        $skeleton = $this->_initFormType();
        $redirectUrl = $this->getUrl('adminhtml/*/*');
        if ($skeleton->getId()) {
            try {
                $hasError = false;
                /** @var $formType \Magento\Eav\Model\Form\Type */
                $formType = $this->_formTypeFactory->create();
                $formType->addData(
                    [
                        'code' => $skeleton->getCode(),
                        'label' => $this->getRequest()->getPost('label'),
                        'theme' => $this->getRequest()->getPost('theme'),
                        'store_id' => $this->getRequest()->getPost('store_id'),
                        'entity_types' => $skeleton->getEntityTypes(),
                        'is_system' => 0,
                    ]
                );
                $formType->save();
                $formType->createFromSkeleton($skeleton);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $hasError = true;
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $hasError = true;
                $this->messageManager->addException($e, __("We can't save the form type right now."));
            }
            if ($hasError) {
                $this->_getSession()->setFormData($this->getRequest()->getPostValue());
                $redirectUrl = $this->getUrl('adminhtml/*/new');
            } else {
                $redirectUrl = $this->getUrl('adminhtml/*/edit/', ['type_id' => $formType->getId()]);
            }
        }

        $this->getResponse()->setRedirect($redirectUrl);
    }
}
