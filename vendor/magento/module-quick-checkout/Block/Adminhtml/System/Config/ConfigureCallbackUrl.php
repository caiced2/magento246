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
 * Configure get account callback URL
 *
 * @api
 */
class ConfigureCallbackUrl extends Field
{
    /**
     * Set template
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Magento_QuickCheckout::system/config/configure_callback_url.phtml');
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
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
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
                'ajax_url' => $this->_urlBuilder->getUrl('quick_checkout/system_config/configurecallbackurl')
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
            'website_id' => $this->_request->getParam('website'),
            'elementId' => $this->getHtmlId(),
            'successText' => __('Callback URL updated.'),
            'alertTitle' => __('Updating callback URL failed.'),
            'alertContent' => __('Updating callback URL failed. Please try again later.'),
            'systemErrorText' => __('Unable to update callback URL. Please try again later.'),
        ];

        return json_encode($config);
    }
}
