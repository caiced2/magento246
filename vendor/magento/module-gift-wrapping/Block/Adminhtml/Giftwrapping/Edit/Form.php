<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Block\Adminhtml\Giftwrapping\Edit;

use Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Price;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\GiftWrapping\Block\Adminhtml\Giftwrapping\Form\Renderer\Element as ElementRenderer;
use Magento\GiftWrapping\Block\Adminhtml\Giftwrapping\Helper\Image;
use Magento\GiftWrapping\Model\Wrapping;

/**
 * GiftWrapping Form
 */
class Form extends Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * @var CurrencyInterface
     */
    private $currency;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param CurrencyInterface $currency
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Directory\Helper\Data $directoryHelper,
        CurrencyInterface $currency,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_directoryHelper = $directoryHelper;
        parent::__construct($context, $registry, $formFactory, $data);

        $this->currency = $currency;
    }

    /**
     * Intialize form
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('magento_giftwrapping_form');
        $this->setTitle(__('Gift Wrapping Information'));
    }

    /**
     * Prepares layout and set element renderer
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        \Magento\Framework\Data\Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock(
                ElementRenderer::class,
                $this->getNameInLayout() . '_element_gift_wrapping'
            )
        );
    }

    /**
     * Prepare edit form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_giftwrapping_model');

        $actionParams = ['store' => $model->getStoreId()];
        if ($model->getId()) {
            $actionParams['id'] = $model->getId();
        }
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('adminhtml/*/save', $actionParams),
                    'method' => 'post',
                    'field_name_suffix' => 'wrapping',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Gift Wrapping Information')]);
        $this->_addElementTypes($fieldset);

        $this->prepareFields($fieldset, $model);

        if (!$model->getId()) {
            $model->setData('status', '1');
        }

        if ($model->hasTmpImage()) {
            $fieldset->addField('tmp_image', 'hidden', ['name' => 'tmp_image']);
        }
        $this->setForm($form);
        $form->setValues($model->getData());
        $form->setDataObject($model);
        $form->setUseContainer(true);

        return parent::_prepareForm();
    }

    /**
     * Retrieve Additional Element Types
     *
     * @return array
     * @codeCoverageIgnore
     */
    protected function _getAdditionalElementTypes()
    {
        return ['image' => Image::class];
    }

    /**
     * Prepare form fields
     *
     * @param Fieldset $fieldset
     * @param Wrapping $model
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    protected function prepareFields($fieldset, $model)
    {
        $fieldset->addField(
            'design',
            'text',
            [
                'label' => __('Gift Wrapping Design'),
                'name' => 'design',
                'required' => true,
                'value' => $model->getDesign(),
                'scope' => 'store'
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'website_ids',
                'multiselect',
                [
                    'name' => 'website_ids',
                    'required' => true,
                    'label' => __('Websites'),
                    'values' => $this->_systemStore->getWebsiteValuesForForm(),
                    'value' => $model->getWebsiteIds()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                Element::class
            );
            $field->setRenderer($renderer);
        }

        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'name' => 'status',
                'required' => true,
                'options' => ['1' => __('Enabled'), '0' => __('Disabled')]
            ]
        );

        $fieldset->addType('price', Price::class);
        $baseCurrencyCode = $this->_directoryHelper->getBaseCurrencyCode();
        $currencySymbol = $this->currency->getCurrency($baseCurrencyCode)->getSymbol();
        $fieldset->addField(
            'base_price',
            'price',
            [
                'label' => __('Price'),
                'name' => 'base_price',
                'required' => true,
                'class' => 'validate-not-negative-number',
                'after_element_html' => '<span>' . $currencySymbol . '</span>'
            ]
        );

        $fieldset->addField(
            'image',
            'image',
            [
                'label' => __('Image'),
                'name' => 'image_name',
                'note' => 'Gift wrapping images with the portrait layout are not scaled when displayed on the ' .
                    'storefront.',
            ]
        );
    }
}
