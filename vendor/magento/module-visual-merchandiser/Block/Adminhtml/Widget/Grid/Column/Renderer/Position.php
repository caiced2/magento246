<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer;
use Magento\Framework\DataObject;

/**
 * Grid column position renderer.
 */
class Position extends Renderer\Number
{
    /**
     * @inheritDoc
     */
    public function render(DataObject $row)
    {
        if ($this->getColumn()->getEditable()) {
            $input = $this->_getInputValueElement($row);
            $top = (string) __('Top');
            $bottom = (string)  __('Bottom');

            $html = <<<HTML
<div class="position">
    <a href="#" class="move-top icon-backward"><span>{$top}</span></a>
    {$input}
    <a href="#" class="move-bottom icon-forward"><span>{$bottom}</span></a>
</div>
HTML;
        } else {
            $html = (string) $this->_getValue($row);
        }

        return $html;
    }
}
