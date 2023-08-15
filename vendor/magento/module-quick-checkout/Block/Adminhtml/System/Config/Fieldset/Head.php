<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Element\Template;
use Magento\Config\Block\System\Config\Form\Fieldset;

/**
 * @api
 */
class Head extends Fieldset
{
    /**
     * Expanded _construct
     *
     * @return void
     */
    public function _construct()
    {
        $this->isCollapsedDefault = true;
    }

    /**
     * Return header comment
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        if ($element->getComment()) {
            return parent::_getHeaderCommentHtml($element);
        }
        $overviewHtml = $this->getLayout()
            ->createBlock(Template::class)
            ->setTemplate('Magento_QuickCheckout::system/config/fieldset/head/overview.phtml')
            ->toHtml();
        return $overviewHtml . parent::_getHeaderCommentHtml($element);
    }
}
