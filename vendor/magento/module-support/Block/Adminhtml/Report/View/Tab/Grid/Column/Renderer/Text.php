<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Report\View\Tab\Grid\Column\Renderer;

/**
 * Grid column widget for rendering grid cells
 */
class Text extends \Magento\Support\Block\Adminhtml\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Support\Model\Report\HtmlGenerator
     */
    protected $htmlGenerator;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Support\Model\Report\Config $reportConfig
     * @param \Magento\Support\Model\Report\HtmlGenerator $htmlGenerator
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Support\Model\Report\Config $reportConfig,
        \Magento\Support\Model\Report\HtmlGenerator $htmlGenerator,
        array $data = []
    ) {
        $this->htmlGenerator = $htmlGenerator;
        parent::__construct($context, $reportConfig, $data);
    }

    /**
     * Render grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return mixed
     */
    public function _getValue(\Magento\Framework\DataObject $row)
    {
        $text = parent::_getValue($row);
        return $this->htmlGenerator->getGridCellHtml(__($text), $text);
    }
}
