<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ScheduledImportExport\Block\Adminhtml\Scheduled\Operation\Edit;

/**
 * Scheduled operation create/edit form
 *
 * @method string getGeneralSettingsLabel() getGeneralSettingsLabel()
 * @method string getFileSettingsLabel() getFileSettingsLabel()
 * @method string getEmailSettingsLabel() getEmailSettingsLabel()
 * @method Form setGeneralSettingsLabel() setGeneralSettingsLabel(string $value)
 * @method Form setFileSettingsLabel() setFileSettingsLabel(string $value)
 * @method Form setEmailSettingsLabel() setEmailSettingsLabel(string $value)
 */
abstract class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data
     */
    protected $_operationData;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_sourceYesno;

    /**
     * @var \Magento\Config\Model\Config\Source\Email\Identity
     */
    protected $_emailIdentity;

    /**
     * @var \Magento\Config\Model\Config\Source\Email\Method
     */
    protected $_emailMethod;

    /**
     * @var \Magento\Framework\Option\ArrayPool
     */
    protected $_optionArrayPool;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Option\ArrayPool $optionArrayPool
     * @param \Magento\Config\Model\Config\Source\Email\Method $emailMethod
     * @param \Magento\Config\Model\Config\Source\Email\Identity $emailIdentity
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data $operationData
     * @param \Magento\Config\Model\Config\Source\Yesno $sourceYesno
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Option\ArrayPool $optionArrayPool,
        \Magento\Config\Model\Config\Source\Email\Method $emailMethod,
        \Magento\Config\Model\Config\Source\Email\Identity $emailIdentity,
        \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data $operationData,
        \Magento\Config\Model\Config\Source\Yesno $sourceYesno,
        \Magento\Framework\Stdlib\StringUtils $string,
        array $data = []
    ) {
        $this->_optionArrayPool = $optionArrayPool;
        $this->_emailMethod = $emailMethod;
        $this->_emailIdentity = $emailIdentity;
        $this->_operationData = $operationData;
        $this->_sourceYesno = $sourceYesno;
        $this->string = $string;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare general form for scheduled operation
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var $operation \Magento\ScheduledImportExport\Model\Scheduled\Operation */
        $operation = $this->_coreRegistry->registry('current_operation');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'name' => 'scheduled_operation']]
        );
        // settings information
        $this->_addGeneralSettings($form, $operation);

        // file information
        $this->_addFileSettings($form, $operation);

        // email notifications
        $this->_addEmailSettings($form, $operation);

        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setAction($this->getUrl('adminhtml/*/save'));

        $this->setForm($form);
        $startTime = $operation->getStartTime();
        if (is_array($startTime)) {
            $operation->setStartTime(join(',', $operation->getStartTime()));
        }
        $operation->setStartTime(str_replace(':', ',', $operation->getStartTime() ?? ''));

        return $this;
    }

    /**
     * Add general information fieldset to form
     *
     * @param \Magento\Framework\Data\Form $form
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation $operation
     * @return $this
     */
    protected function _addGeneralSettings($form, $operation)
    {
        $fieldset = $form->addFieldset('operation_settings', ['legend' => $this->getGeneralSettingsLabel()]);

        if ($operation->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id', 'required' => true]);
        }
        $fieldset->addField('operation_type', 'hidden', ['name' => 'operation_type', 'required' => true]);

        $fieldset->addField(
            'name',
            'text',
            ['name' => 'name', 'title' => __('Name'), 'label' => __('Name'), 'required' => true]
        );

        $fieldset->addField(
            'details',
            'textarea',
            ['name' => 'details', 'title' => __('Description'), 'label' => __('Description'), 'required' => false]
        );

        $entities = $this->_optionArrayPool->get(
            'Magento\ImportExport\Model\Source\\' . $this->string->upperCaseWords(
                $operation->getOperationType()
            ) . '\Entity'
        )->toOptionArray();

        $fieldset->addField(
            'entity',
            'select',
            [
                'name' => 'entity_type',
                'title' => __('Entity Type'),
                'label' => __('Entity Type'),
                'required' => true,
                'values' => $entities
            ]
        );

        $fieldset->addField(
            'start_time',
            'time',
            ['name' => 'start_time', 'title' => __('Start Time'), 'label' => __('Start Time'), 'required' => true]
        );

        $fieldset->addField(
            'freq',
            'select',
            [
                'name' => 'freq',
                'title' => __('Frequency'),
                'label' => __('Frequency'),
                'required' => true,
                'values' => $this->_operationData->getFrequencyOptionArray()
            ]
        );

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'title' => __('Status'),
                'label' => __('Status'),
                'required' => true,
                'values' => $this->_operationData->getStatusesOptionArray()
            ]
        );

        return $this;
    }

    /**
     * Add file information fieldset to form
     *
     * @param \Magento\Framework\Data\Form $form
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation $operation
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _addFileSettings($form, $operation)
    {
        $fieldset = $form->addFieldset('file_settings', ['legend' => $this->getFileSettingsLabel()]);

        $fieldset->addField(
            'server_type',
            'select',
            [
                'name' => 'file_info[server_type]',
                'title' => __('Server Type'),
                'label' => __('Server Type'),
                'required' => true,
                'values' => $this->_operationData->getServerTypesOptionArray()
            ]
        );

        $fieldset->addField(
            'file_path',
            'text',
            [
                'name' => 'file_info[file_path]',
                'title' => __('File Directory'),
                'label' => __('File Directory'),
                'required' => true,
                'note' => __(
                    'For Type "Local Server" use relative path to Magento installation, '
                    . ' e.g. var/export, var/import, var/export/some/dir. If remote storage is configured, '
                    . 'use paths like import_export/export, import_export/import, import_export/export/some/dir.'
                )
            ]
        );

        $fieldset->addField(
            'host',
            'text',
            [
                'name' => 'file_info[host]',
                'title' => __('FTP Host[:Port]'),
                'label' => __('FTP Host[:Port]'),
                'class' => 'ftp-server server-dependent'
            ]
        );

        $fieldset->addField(
            'user',
            'text',
            [
                'name' => 'file_info[user]',
                'title' => __('User Name'),
                'label' => __('User Name'),
                'class' => 'ftp-server server-dependent'
            ]
        );

        $fieldset->addField(
            'password',
            'password',
            [
                'name' => 'file_info[password]',
                'title' => __('Password'),
                'label' => __('Password'),
                'class' => 'ftp-server server-dependent'
            ]
        );

        $fieldset->addField(
            'file_mode',
            'select',
            [
                'name' => 'file_info[file_mode]',
                'title' => __('File Mode'),
                'label' => __('File Mode'),
                'values' => $this->_operationData->getFileModesOptionArray(),
                'class' => 'ftp-server server-dependent'
            ]
        );

        $fieldset->addField(
            'passive',
            'select',
            [
                'name' => 'file_info[passive]',
                'title' => __('Passive Mode'),
                'label' => __('Passive Mode'),
                'values' => $this->_sourceYesno->toOptionArray(),
                'class' => 'ftp-server server-dependent'
            ]
        );

        return $this;
    }

    /**
     * Add file information fieldset to form
     *
     * @param \Magento\Framework\Data\Form $form
     * @param \Magento\ScheduledImportExport\Model\Scheduled\Operation $operation
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _addEmailSettings($form, $operation)
    {
        $fieldset = $form->addFieldSet('email_settings', ['legend' => $this->getEmailSettingsLabel()]);

        $fieldset->addField(
            'email_receiver',
            'select',
            [
                'name' => 'email_receiver',
                'title' => __('Failed Email Receiver'),
                'label' => __('Failed Email Receiver'),
                'values' => $this->_emailIdentity->toOptionArray()
            ]
        );

        $fieldset->addField(
            'email_sender',
            'select',
            [
                'name' => 'email_sender',
                'title' => __('Failed Email Sender'),
                'label' => __('Failed Email Sender'),
                'values' => $this->_emailIdentity->toOptionArray()
            ]
        );

        $fieldset->addField(
            'email_template',
            'select',
            [
                'name' => 'email_template',
                'title' => __('Failed Email Template'),
                'label' => __('Failed Email Template')
            ]
        );

        $fieldset->addField(
            'email_copy',
            'text',
            [
                'name' => 'email_copy',
                'title' => __('Send Failed Email Copy To'),
                'label' => __('Send Failed Email Copy To')
            ]
        );

        $fieldset->addField(
            'email_copy_method',
            'select',
            [
                'name' => 'email_copy_method',
                'title' => __('Send Failed Email Copy Method'),
                'label' => __('Send Failed Email Copy Method'),
                'values' => $this->_emailMethod->toOptionArray()
            ]
        );

        return $this;
    }

    /**
     * Set values to form from operation model
     *
     * @param array $data
     * @return $this
     */
    protected function _setFormValues(array $data)
    {
        if (!is_object($this->getForm())) {
            return false;
        }
        if (isset($data['file_info'])) {
            $fileInfo = $data['file_info'];
            unset($data['file_info']);
            if (is_array($fileInfo)) {
                $data = array_merge($data, $fileInfo);
            }
        }
        if (isset($data['entity_type'])) {
            $data['entity'] = $data['entity_type'];
        }
        $this->getForm()->setValues($data);
        return $this;
    }
}
