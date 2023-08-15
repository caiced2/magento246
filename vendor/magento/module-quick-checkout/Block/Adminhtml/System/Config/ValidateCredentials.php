<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Quick checkout credentials block
 *
 * @api
 */
class ValidateCredentials extends Field
{
    /**
     * Set template
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Magento_QuickCheckout::system/config/validate_credentials.phtml');
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $buttonLabel = $originalData['button_label'];
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('quick_checkout/system_config/validatecredentials')
            ]
        );

        return $this->_toHtml();
    }

    /**
     * Get block config as JSON
     *
     * @return string
     */
    public function getBlockConfigAsJson(): string
    {
        $config = [
            'url' => $this->getAjaxUrl(),
            'elementId' => $this->getHtmlId(),
            'successText' => __('Your credentials are valid.'),
            'alertTitle' => __('Credential validation failure.'),
            'alertContent' => __(
                'Provided credentials are invalid. Please verify the private and publishable keys.'
            ),
            'systemErrorText' => __('Unable to validate credentials. Please try again later.'),
            'fieldMapping' => [
                'api_key' => 'checkout_quick_checkout_credentials_api_key',
                'signing_secret' => 'checkout_quick_checkout_credentials_signing_secret',
                'publishable_key' => 'checkout_quick_checkout_credentials_publishable_key',
            ],
        ];

        return json_encode($config);
    }
}
