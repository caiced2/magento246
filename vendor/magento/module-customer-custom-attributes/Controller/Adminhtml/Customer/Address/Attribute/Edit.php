<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

class Edit extends \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute
{
    /**
     * Edit attribute action
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $attributeId = $this->getRequest()->getParam('attribute_id');
        /* @var $attributeObject \Magento\Customer\Model\Attribute */
        $attributeObject = $this->_initAttribute()->setEntityTypeId($this->_getEntityType()->getId());

        if ($attributeId) {
            $attributeObject->load($attributeId);
            if (!$attributeObject->getId()) {
                $this->messageManager->addError(__('This attribute no longer exists.'));
                $this->_redirect('adminhtml/*/');
                return;
            }
            if ($attributeObject->getEntityTypeId() != $this->_getEntityType()->getId()) {
                $this->messageManager->addError(__('You cannot edit this attribute.'));
                $this->_redirect('adminhtml/*/');
                return;
            }
        }

        // restore attribute data
        $attributeData = $this->_getSession()->getAttributeData(true);
        if (!empty($attributeData)) {
            $attributeObject->setData($attributeData);
        }

        // register attribute object
        $this->_coreRegistry->register('entity_attribute', $attributeObject);

        $label = $attributeObject->getId() ? __(
            'Edit Customer Address Attribute'
        ) : __(
            'New Customer Address Attribute'
        );

        $this->_initAction()->_addBreadcrumb($label, $label);
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Customer Address Attributes'));
        $attributeId ? $this->_view->getPage()->getConfig()->getTitle()->prepend($attributeObject->getFrontendLabel())
            : $this->_view->getPage()->getConfig()->getTitle()->prepend(__('New Customer Address Attribute'));
        $this->_view->renderLayout();
    }
}
