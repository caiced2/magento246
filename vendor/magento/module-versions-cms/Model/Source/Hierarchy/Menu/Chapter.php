<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\Source\Hierarchy\Menu;

/**
 * CMS Hierarchy Menu source model for Chapter/Section options
 *
 */
class Chapter implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return options for Chapter/Section meta links
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['label' => __('No'), 'value' => ''],
            ['label' => __('Chapter'), 'value' => 'chapter'],
            ['label' => __('Section'), 'value' => 'section'],
            ['label' => __('Both'), 'value' => 'both'],
        ];

        return $options;
    }
}
