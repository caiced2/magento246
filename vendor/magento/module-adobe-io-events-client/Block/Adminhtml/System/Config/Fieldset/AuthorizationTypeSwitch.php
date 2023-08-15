<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Block\Adminhtml\System\Config\Fieldset;

use Magento\Config\Block\System\Config\Form\Fieldset;

/**
 * Adds a javascript for clearing workspace configuration field when authorization type is changed.
 */
class AuthorizationTypeSwitch extends Fieldset
{
    /**
     * @inheritDoc
     */
    protected function _getExtraJs($element)
    {
        $comment = '<p class="note"><span>Update configuration with new authorization value found in ' .
            '&lt;workspace-name&gt;.json file.</span></p>';
        $id = $element->getId();
        $script = "require(['jquery'], function($){
            $('#{$id}_authorization_type').on('change', function() {
                  $('#{$id}_workspace_configuration').val('');
                  $('#{$id}_workspace_configuration').parent().find('p.note').remove();
                  $('#{$id}_workspace_configuration').parent().append('{$comment}')
            });
        });";

        return $this->_jsHelper->getScript($script);
    }
}
