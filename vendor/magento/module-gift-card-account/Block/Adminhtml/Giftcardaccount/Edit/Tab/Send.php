<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit\Tab;

use Magento\Backend\Block\Widget\Form;

class Send extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form fields
     *
     * @return $this
     */
    public function initForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('_send');

        $model = $this->_coreRegistry->registry('current_giftcardaccount');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Send Gift Card')]);

        $fieldset->addField(
            'recipient_email',
            'text',
            [
                'label' => __('Recipient Email'),
                'title' => __('Recipient Email'),
                'class' => 'validate-email',
                'name' => 'recipient_email'
            ]
        );

        $fieldset->addField(
            'recipient_name',
            'text',
            ['label' => __('Recipient Name'), 'title' => __('Recipient Name'), 'name' => 'recipient_name']
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'store_id',
                'select',
                [
                    'name' => 'recipient_store',
                    'label' => __('Send Email from the Following Store View'),
                    'title' => __('Send Email from the Following Store View'),
                    'after_element_html' => $this->_getStoreIdScript()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element::class
            );
            $field->setRenderer($renderer);
        }

        $fieldset->addField('action', 'hidden', ['name' => 'send_action']);

        $form->setValues($model->getData());
        $this->setForm($form);
        return $this;
    }

    /**
     * @return string
     */
    protected function _getStoreIdScript()
    {
        $websiteStores = [];
        foreach ($this->_storeManager->getWebsites() as $websiteId => $website) {
            $websiteStores[$websiteId] = [];
            foreach ($website->getGroups() as $groupId => $group) {
                $websiteStores[$websiteId][$groupId] = ['name' => $group->getName()];
                foreach ($group->getStores() as $storeId => $store) {
                    $websiteStores[$websiteId][$groupId]['stores'][] = [
                        'id' => $storeId,
                        'name' => $store->getName(),
                    ];
                }
            }
        }

        $websiteStores = $this->_jsonEncoder->encode($websiteStores);

        $result = '<script>require(["prototype"], function(){' . "\n";
        $result .= "var websiteStores = {$websiteStores};";
        $result .= "Event.observe('_infowebsite_id', 'change', setCurrentStores);";
        $result .= "setCurrentStores();";
        $result .= 'function setCurrentStores(){
            var wSel = $("_infowebsite_id");
            var sSel = $("_sendstore_id");

            sSel.innerHTML = \'\';
            var website = wSel.options[wSel.selectedIndex].value;
            if (websiteStores[website]) {
                groups = websiteStores[website];
                for (groupKey in groups) {
                    group = groups[groupKey];
                    optionGroup = document.createElement("OPTGROUP");
                    optionGroup.label = group["name"];
                    sSel.appendChild(optionGroup);

                    stores = group["stores"];
                    for (i=0; i < stores.length; i++) {
                        var option = document.createElement("option");
                        option.appendChild(document.createTextNode(stores[i]["name"]));
                        option.setAttribute("value", stores[i]["id"]);
                        optionGroup.appendChild(option);
                    }
                }
            }
            else {
              var option = document.createElement("option");
              option.appendChild(document.createTextNode(\'' .
            __(
                '-- First Please Select a Website --'
            ) . '\'));
              sSel.appendChild(option);
            }
        }
        });</script>';

        return $result;
    }
}
