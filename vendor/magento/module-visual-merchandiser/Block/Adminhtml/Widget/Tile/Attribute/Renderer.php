<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget\Tile\Attribute;

/**
 * @method string getLabel()
 * @method string getValue()
 */
class Renderer extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Framework\Escaper
     */
    public $escaper;

    /**
     * @param \Magento\Framework\Escaper $escaper
     * @param array $data
     */
    public function __construct(\Magento\Framework\Escaper $escaper, array $data = [])
    {
        parent::__construct($data);
        $this->escaper = $escaper;
    }

    /**
     * @return string
     */
    public function render()
    {
        return '<span>' . $this->escaper->escapeHtml($this->getLabel() . ': ' . $this->getValue()) . '</span></br>';
    }
}
