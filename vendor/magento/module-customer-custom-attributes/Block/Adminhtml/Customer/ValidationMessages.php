<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Block\Adminhtml\Customer;

use Magento\Framework\View\Element\Messages;

/**
 * Customer Custom Attributes create page validation messages block.
 */
class ValidationMessages extends Messages
{
    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        $html = parent::_toHtml();

        if (empty($html)) {
            $html = '<div class="messages"/>';
        }

        return $html;
    }
}
