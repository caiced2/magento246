<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Formtype;

class Save extends \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Formtype
{
    /**
     * Save Form Type Tree data
     *
     * @param \Magento\Eav\Model\Form\Type $formType
     * @param array $data
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _saveTreeData($formType, array $data)
    {
        /** @var $fieldsetCollection \Magento\Eav\Model\ResourceModel\Form\Fieldset\Collection */
        $fieldsetCollection = $this->_fieldsetsFactory->create();
        $fieldsetCollection->addTypeFilter($formType)->setSortOrder();

        /** @var $elementCollection \Magento\Eav\Model\ResourceModel\Form\Element\Collection */
        $elementCollection = $this->_elementsFactory->create();
        $elementCollection->addTypeFilter($formType)->setSortOrder();

        $fsUpdate = [];
        $fsInsert = [];
        $fsDelete = [];
        $attributes = [];

        //parse tree data
        foreach ($data as $k => $v) {
            if (strpos($k, 'f_') === 0) {
                $fsInsert[] = $v;
            } elseif (is_numeric($k)) {
                $fsUpdate[$k] = $v;
            } elseif (strpos($k, 'a_') === 0) {
                $v['node_id'] = substr($v['node_id'], 2);
                $attributes[] = $v;
            }
        }

        foreach ($fieldsetCollection as $fieldset) {
            /* @var $fieldset \Magento\Eav\Model\Form\Fieldset */
            if (!isset($fsUpdate[$fieldset->getId()])) {
                // collect deleted fieldsets
                $fsDelete[$fieldset->getId()] = $fieldset;
            } else {
                // update fieldset
                $fsData = $fsUpdate[$fieldset->getId()];
                $fieldset->setCode(
                    $fsData['code']
                )->setLabels(
                    $fsData['labels']
                )->setSortOrder(
                    $fsData['sort_order']
                )->save();
            }
        }

        // insert new fieldsets
        $fsMap = [];
        foreach ($fsInsert as $fsData) {
            /** @var $fieldset \Magento\Eav\Model\Form\Fieldset */
            $fieldset = $this->_fieldsetFactory->create();
            $fieldset->setTypeId(
                $formType->getId()
            )->setCode(
                $fsData['code']
            )->setLabels(
                $fsData['labels']
            )->setSortOrder(
                $fsData['sort_order']
            )->save();
            $fsMap[$fsData['node_id']] = $fieldset->getId();
        }

        // update attributes
        foreach ($attributes as $attrData) {
            $element = $elementCollection->getItemById($attrData['node_id']);
            if (!$element) {
                continue;
            }
            if (empty($attrData['parent'])) {
                $fieldsetId = null;
            } elseif (is_numeric($attrData['parent'])) {
                $fieldsetId = (int)$attrData['parent'];
            } elseif (strpos($attrData['parent'], 'f_') === 0) {
                $fieldsetId = $fsMap[$attrData['parent']];
            } else {
                continue;
            }

            $element->setFieldsetId($fieldsetId)->setSortOrder($attrData['sort_order'])->save();
        }

        // delete fieldsets
        foreach ($fsDelete as $fieldset) {
            $fieldset->delete();
        }
    }

    /**
     * Save form Type
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $formType = $this->_initFormType();
        $redirectUrl = $this->getUrl('adminhtml/*/index');
        if ($this->getRequest()->isPost() && $formType->getId()) {
            $request = $this->getRequest();
            try {
                $hasError = false;
                $formType->setLabel($request->getPost('label'));
                $formType->save();

                $treeData = $this->_objectManager->get(
                    \Magento\Framework\Json\Helper\Data::class
                )->jsonDecode(
                    $request->getPost('form_type_data')
                );
                if (!empty($treeData) && is_array($treeData)) {
                    $this->_saveTreeData($formType, $treeData);
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $hasError = true;
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $hasError = true;
                $this->messageManager->addException($e, __("We can't save the form type right now."));
            }

            if ($hasError) {
                $this->_getSession()->setFormData($this->getRequest()->getPostValue());
            }
            if ($hasError || $request->getPost('continue_edit')) {
                $redirectUrl = $this->getUrl('adminhtml/*/edit', ['type_id' => $formType->getId()]);
            }
        }
        $this->getResponse()->setRedirect($redirectUrl);
    }
}
