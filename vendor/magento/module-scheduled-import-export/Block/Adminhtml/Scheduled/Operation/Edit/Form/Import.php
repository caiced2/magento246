<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Block\Adminhtml\Scheduled\Operation\Edit\Form;

use \Magento\ImportExport\Model\Import as ImportModel;

/**
 * Scheduled import create/edit form
 *
 * @method Import setGeneralSettingsLabel(string $value)
 * @method Import setFileSettingsLabel(string $value)
 * @method Import setEmailSettingsLabel(string $value)
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Import extends \Magento\ScheduledImportExport\Block\Adminhtml\Scheduled\Operation\Edit\Form
{
    /**
     * Basic import model
     *
     * @var ImportModel
     */
    protected $_importModel;

    /**
     * @var \Magento\Config\Model\Config\Source\Email\TemplateFactory
     */
    protected $_templateFactory;

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
     * @param \Magento\Config\Model\Config\Source\Email\TemplateFactory $templateFactory
     * @param ImportModel $importModel
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
        \Magento\Config\Model\Config\Source\Email\TemplateFactory $templateFactory,
        ImportModel $importModel,
        array $data = []
    ) {
        $this->_templateFactory = $templateFactory;
        $this->_importModel = $importModel;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $optionArrayPool,
            $emailMethod,
            $emailIdentity,
            $operationData,
            $sourceYesno,
            $string,
            $data
        );
    }

    /**
     * Prepare form for import operation
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)+
     */
    protected function _prepareForm()
    {
        $this->setGeneralSettingsLabel(__('Import Settings'));
        $this->setFileSettingsLabel(__('Import File Information'));
        $this->setEmailSettingsLabel(__('Import Failed Emails'));

        parent::_prepareForm();
        $form = $this->getForm();

        /** @var $fieldset \Magento\Framework\Data\Form\Element\AbstractElement */
        $fieldset = $form->getElement('operation_settings');

        // add behaviour fields
        $uniqueBehaviors = $this->_importModel->getUniqueEntityBehaviors();
        foreach ($uniqueBehaviors as $behaviorCode => $behaviorClass) {
            $fieldset->addField(
                $behaviorCode,
                'select',
                [
                    'name' => 'behavior',
                    'title' => __('Import Behavior'),
                    'label' => __('Import Behavior'),
                    'required' => true,
                    'disabled' => true,
                    'values' => $this->_optionArrayPool->get($behaviorClass)->toOptionArray()
                ],
                'entity'
            );
        }

        $fieldset->addField(
            ImportModel::FIELD_FIELD_SEPARATOR,
            'text',
            [
                'name' => 'file_info[' . ImportModel::FIELD_FIELD_SEPARATOR . ']',
                'label' => __('Field separator'),
                'title' => __('Field separator'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            ImportModel::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR,
            'text',
            [
                'name' => 'file_info[' . ImportModel::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR . ']',
                'label' => __('Multiple value separator'),
                'title' => __('Multiple value separator'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'force_import',
            'select',
            [
                'name' => 'force_import',
                'title' => __('On Error'),
                'label' => __('On Error'),
                'required' => true,
                'values' => $this->_operationData->getForcedImportOptionArray()
            ],
            'freq'
        );

        $form->getElement(
            'email_template'
        )->setValues(
            $this->_templateFactory->create()->setPath('magento_scheduledimportexport_import_failed')->toOptionArray()
        );

        $fieldset = $form->getElement('file_settings');
        $fieldset->addField(
            'file_name',
            'text',
            [
                'name' => 'file_info[file_name]',
                'title' => __('File Name'),
                'label' => __('File Name'),
                'required' => true
            ],
            'file_path'
        );

        $note = 'For Type "Local Server" use relative path to Magento installation, '
            . 'e.g. var/export, var/import, var/export/some/dir. If remote storage is configured, '
            . 'use paths like import_export/export, import_export/import, import_export/export/some/dir.';
        $fieldset->addField(
            ImportModel::FIELD_NAME_IMG_FILE_DIR,
            'text',
            [
                'name' => 'file_info[' .ImportModel::FIELD_NAME_IMG_FILE_DIR . ']',
                'label' => __('Images File Directory'),
                'title' => __('Images File Directory'),
                'required' => false,
                'class' => 'input-text',
                'note' => __($note),
            ],
            'file_name'
        );

        /** @var $element \Magento\Framework\Data\Form\Element\AbstractElement */
        $element = $form->getElement('entity');
        $element->setData('onchange', 'varienImportExportScheduled.handleEntityTypeSelector();');

        /** @var $operation \Magento\ScheduledImportExport\Model\Scheduled\Operation */
        $operation = $this->_coreRegistry->registry('current_operation');
        $defaultFormValues = [
            ImportModel::FIELD_FIELD_SEPARATOR => ',',
            ImportModel::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR =>
                ImportModel::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
        ];
        $this->_setFormValues(array_merge($defaultFormValues, $operation->getData()));

        return $this;
    }
}
