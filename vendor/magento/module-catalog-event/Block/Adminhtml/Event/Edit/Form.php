<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogEvent\Block\Adminhtml\Event\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\Category as ModelCategory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogEvent\Model\DateResolver;
use Magento\CatalogEvent\Model\Event;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

/**
 * Catalog Events edit form.
 *
 * @api
 * @since 100.0.2
 */
class Form extends Generic
{
    /**
     * Adminhtml data
     *
     * @var Data
     */
    protected $_adminhtmlData = null;

    /**
     * Category model factory
     *
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var DateResolver
     */
    private $dateResolver;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Data $adminhtmlData
     * @param CategoryFactory $categoryFactory
     * @param array $data
     * @param DateResolver|null $dateResolver
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Data $adminhtmlData,
        CategoryFactory $categoryFactory,
        array $data = [],
        ?DateResolver $dateResolver = null
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->_adminhtmlData = $adminhtmlData;
        $this->_categoryFactory = $categoryFactory;
        $this->dateResolver = $dateResolver ?: ObjectManager::getInstance()->get(DateResolver::class);
    }

    /**
     * Return form action url
     *
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('adminhtml/*/save', ['_current' => true]);
    }

    /**
     * Prepares layout, set custom renderers
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        \Magento\Framework\Data\Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock(
                \Magento\CatalogEvent\Block\Adminhtml\Form\Renderer\Fieldset\Element::class,
                $this->getNameInLayout() . '_fieldset_element_catalog_event'
            )
        );
    }

    /**
     * Prepares event edit form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getActionUrl(),
                    'method' => 'post',
                    'field_name_suffix' => 'catalogevent',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );

        $form->setHtmlIdPrefix('event_edit_');

        $fieldset = $form->addFieldset(
            'general_fieldset',
            ['legend' => __('Catalog Event Information'), 'class' => 'fieldset-wide']
        );

        $this->_addElementTypes($fieldset);

        /** @var ModelCategory $currentCategory */
        $currentCategory = $this->_categoryFactory->create()->load($this->getEvent()->getCategoryId());

        $fieldset->addField('category_name', 'note', ['id' => 'category_span', 'label' => __('Category')]);

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);

        $fieldset->addField(
            'date_start',
            'date',
            [
                'label' => __('Start Date'),
                'name' => 'date_start',
                'required' => true,
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'class' => 'admin__control-text'
            ]
        );

        $fieldset->addField(
            'date_end',
            'date',
            [
                'label' => __('End Date'),
                'name' => 'date_end',
                'required' => true,
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'class' => 'admin__control-text'
            ]
        );

        $fieldset->addField('image', 'image', ['label' => __('Image'), 'scope' => 'store', 'name' => 'image']);

        $fieldset->addField(
            'sort_order',
            'text',
            ['label' => __('Sort Order'), 'name' => 'sort_order', 'class' => 'validate-num qty']
        );

        $statuses = [
            Event::STATUS_UPCOMING => __('Upcoming'),
            Event::STATUS_OPEN => __('Open'),
            Event::STATUS_CLOSED => __('Closed'),
        ];

        $fieldset->addField(
            'display_state_array',
            'checkboxes',
            [
                'label' => __('Display Countdown Ticker On'),
                'name' => 'display_state[]',
                'values' => [
                    Event::DISPLAY_CATEGORY_PAGE => __('Category Page'),
                    Event::DISPLAY_PRODUCT_PAGE => __('Product Page'),
                ]
            ]
        );

        if ($this->getEvent()->getId()) {
            $fieldset->addField(
                'status',
                'note',
                [
                    'label' => __('Status'),
                    'text' => $statuses[$this->getEvent()->getStatus()]
                ]
            );
        }

        $form->setValues($this->getEvent()->getData());

        if ($currentCategory && $this->getEvent()->getId()) {
            $form->getElement(
                'category_name'
            )->setText(
                '<a href="' . $this->_adminhtmlData->getUrl(
                    'catalog/category/edit',
                    ['clear' => 1, 'id' => $currentCategory->getId()]
                ) . '">' . $this->escapeHtml($currentCategory->getName()) . '</a>'
            );
        } else {
            $form->getElement(
                'category_name'
            )->setText(
                '<a href="' . $this->getParentBlock()->getBackUrl() . '">' .
                $this->escapeHtml($currentCategory->getName()) . '</a>'
            );
        }

        $form->getElement('date_start')->setValue(
            $this->dateResolver->convertDate($this->getEvent()->getDateStart(), false)
        );
        $form->getElement('date_end')->setValue(
            $this->dateResolver->convertDate($this->getEvent()->getDateEnd(), false)
        );

        if ($this->getEvent()->getDisplayState()) {
            $form->getElement('display_state_array')->setChecked($this->getEvent()->getDisplayState());
        }

        $form->setUseContainer(true);
        $form->setDataObject($this->getEvent());
        $this->setForm($form);

        if ($this->getEvent()->isReadonly()) {
            foreach ($fieldset->getElements() as $element) {
                if ($element->getId() !== 'image') {
                    $element->setReadonly(true, true);
                }
            }
        }

        if ($this->getEvent()->getImageReadonly()) {
            $form->getElement('image')->setReadonly(true, true);
        }
        return parent::_prepareForm();
    }

    /**
     * Retrieve catalog event model
     *
     * @return Event
     */
    public function getEvent()
    {
        return $this->_coreRegistry->registry('magento_catalogevent_event');
    }

    /**
     * Retrieve Additional Element Types
     *
     * @return array
     * @codeCoverageIgnore
     */
    protected function _getAdditionalElementTypes()
    {
        return ['image' => \Magento\CatalogEvent\Block\Adminhtml\Event\Helper\Image::class];
    }
}
