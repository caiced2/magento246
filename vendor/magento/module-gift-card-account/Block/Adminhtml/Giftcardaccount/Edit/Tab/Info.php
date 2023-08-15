<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit\Tab;

class Info extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var string
     */
    protected $_template = 'edit/tab/info.phtml';

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_systemStore = $systemStore;
    }

    /**
     * Init form fields
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function initForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('_info');

        $model = $this->_coreRegistry->registry('current_giftcardaccount');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Information')]);

        if ($model->getId()) {
            $fieldset->addField(
                'code',
                'label',
                ['name' => 'code', 'label' => __('Gift Card Code'), 'title' => __('Gift Card Code')]
            );

            $fieldset->addField(
                'state_text',
                'label',
                ['name' => 'state_text', 'label' => __('Status'), 'title' => __('Status')]
            );
        }

        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Active'),
                'title' => __('Active'),
                'name' => 'status',
                'required' => true,
                'options' => [
                    \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED => __('Yes'),
                    \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_DISABLED => __('No'),
                ]
            ]
        );
        if (!$model->getId()) {
            $model->setData('status', \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED);
        }

        $fieldset->addField(
            'is_redeemable',
            'select',
            [
                'label' => __('Redeemable'),
                'title' => __('Redeemable'),
                'name' => 'is_redeemable',
                'required' => true,
                'options' => [
                    \Magento\GiftCardAccount\Model\Giftcardaccount::REDEEMABLE => __('Yes'),
                    \Magento\GiftCardAccount\Model\Giftcardaccount::NOT_REDEEMABLE => __('No'),
                ]
            ]
        );
        if (!$model->getId()) {
            $model->setData('is_redeemable', \Magento\GiftCardAccount\Model\Giftcardaccount::REDEEMABLE);
        }

        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'website_id',
                'select',
                [
                    'name' => 'website_id',
                    'label' => __('Website'),
                    'title' => __('Website'),
                    'required' => true,
                    'values' => $this->_systemStore->getWebsiteValuesForForm(true)
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element::class
            );
            $field->setRenderer($renderer);
        }

        $fieldset->addType('price', \Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Form\Price::class);

        $note = '';
        if ($this->_storeManager->isSingleStoreMode()) {
            $currencies = $this->_getCurrency();
            $note = '<b>[' . array_shift($currencies) . ']</b>';
        }
        $fieldset->addField(
            'balance',
            'price',
            [
                'label' => __('Balance'),
                'title' => __('Balance'),
                'name' => 'balance',
                'class' => 'validate-number',
                'required' => true,
                'note' => '<div id="balance_currency">' . $note . '</div>'
            ]
        );

        $fieldset->addField(
            'date_expires',
            'date',
            [
                'name' => 'date_expires',
                'label' => __('Expiration Date'),
                'title' => __('Expiration Date'),
                'date_format' => $this->_localeDate->getDateFormat(
                    \IntlDateFormatter::SHORT
                )
            ]
        );

        $form->setValues($model->getData());

        $this->setForm($form);
        return $this;
    }

    /**
     * Get array of base currency codes among all existing web sites
     *
     * @return array
     */
    protected function _getCurrency()
    {
        $result = [];
        $websites = $this->_systemStore->getWebsiteCollection();
        foreach ($websites as $id => $website) {
            $result[$id] = $website->getBaseCurrencyCode();
        }
        return $result;
    }

    /**
     * Encode currency array to Json string
     *
     * @return string
     */
    public function getCurrencyJson()
    {
        $result = $this->_getCurrency();
        return $this->_jsonEncoder->encode($result);
    }

    /**
     * Get is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->_storeManager->isSingleStoreMode();
    }
}
