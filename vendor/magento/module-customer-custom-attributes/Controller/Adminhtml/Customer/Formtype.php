<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer;

/**
 * Adminhtml Manage Form Types Controller
 */
abstract class Formtype extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Eav\Model\Form\TypeFactory
     */
    protected $_formTypeFactory;

    /**
     * @var \Magento\Eav\Model\Form\FieldsetFactory
     */
    protected $_fieldsetFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Form\Fieldset\CollectionFactory
     */
    protected $_fieldsetsFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Form\Element\CollectionFactory
     */
    protected $_elementsFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Eav\Model\Form\TypeFactory $formTypeFactory
     * @param \Magento\Eav\Model\Form\FieldsetFactory $fieldsetFactory
     * @param \Magento\Eav\Model\ResourceModel\Form\Fieldset\CollectionFactory $fieldsetsFactory
     * @param \Magento\Eav\Model\ResourceModel\Form\Element\CollectionFactory $elementsFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Eav\Model\Form\TypeFactory $formTypeFactory,
        \Magento\Eav\Model\Form\FieldsetFactory $fieldsetFactory,
        \Magento\Eav\Model\ResourceModel\Form\Fieldset\CollectionFactory $fieldsetsFactory,
        \Magento\Eav\Model\ResourceModel\Form\Element\CollectionFactory $elementsFactory
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_formTypeFactory = $formTypeFactory;
        $this->_fieldsetFactory = $fieldsetFactory;
        $this->_fieldsetsFactory = $fieldsetsFactory;
        $this->_elementsFactory = $elementsFactory;
        parent::__construct($context);
    }

    /**
     * Load layout, set active menu and breadcrumbs
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_CustomerCustomAttributes::customer_formtype'
        )->_addBreadcrumb(
            __('Customer'),
            __('Customer')
        )->_addBreadcrumb(
            __('Manage Form Types'),
            __('Manage Form Types')
        );
        return $this;
    }

    /**
     * Initialize and return current form type instance
     *
     * @return \Magento\Eav\Model\Form\Type
     */
    protected function _initFormType()
    {
        /** @var $model \Magento\Eav\Model\Form\Type */
        $model = $this->_formTypeFactory->create();
        $typeId = $this->getRequest()->getParam('type_id');
        if (is_numeric($typeId)) {
            $model->load($typeId);
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $this->_coreRegistry->register('current_form_type', $model);
        return $model;
    }

    /**
     * Check is allowed access to action
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(null);
    }
}
