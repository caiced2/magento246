<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ReportGroups
 */
class ReportGroups implements OptionSourceInterface
{
    /**
     * @var \Magento\Support\Model\Report\Config
     */
    protected $config;

    /**
     * Report group options
     *
     * @var array
     */
    protected $options;

    /**
     * Constructor
     *
     * @param \Magento\Support\Model\Report\Config $config
     */
    public function __construct(\Magento\Support\Model\Report\Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $this->options[] = ['label' => '', 'value' => ''];
        $reportGroupOptions = $this->config->getGroupOptions();
        foreach ($reportGroupOptions as $option) {
            $this->options[] = [
                'value' => $option['value'],
                'label' => $option['label'],
            ];
        }

        return $this->options;
    }
}
