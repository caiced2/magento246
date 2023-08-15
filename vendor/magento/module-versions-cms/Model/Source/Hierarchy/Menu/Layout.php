<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\Source\Hierarchy\Menu;

/**
 * CMS Hierarchy Menu source model for Layouts
 */
class Layout implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\ConfigInterface
     */
    protected $_hierarchyConfig;

    /**
     * @param \Magento\VersionsCms\Model\Hierarchy\ConfigInterface $hierarchyConfig
     */
    public function __construct(\Magento\VersionsCms\Model\Hierarchy\ConfigInterface $hierarchyConfig)
    {
        $this->_hierarchyConfig = $hierarchyConfig;
    }

    /**
     * Return options for displaying Hierarchy Menu
     *
     * @param bool $withDefault Include or not default value
     * @return array
     */
    public function toOptionArray($withDefault = false)
    {
        $options = [];
        if ($withDefault) {
            $options[] = ['label' => __('Use default'), 'value' => ''];
        }

        foreach ($this->_hierarchyConfig->getAllMenuLayouts() as $name => $info) {
            $options[] = ['label' => $info['label'], 'value' => $name];
        }

        return $options;
    }
}
