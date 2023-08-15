<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Grid\Column\Renderer;

/**
 * Backend grid item abstract renderer
 */
abstract class AbstractRenderer extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Support\Model\Report\Config
     */
    protected $reportConfig;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Support\Model\Report\Config $reportConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Support\Model\Report\Config $reportConfig,
        array $data = []
    ) {
        $this->reportConfig = $reportConfig;
        parent::__construct($context, $data);
    }
}
