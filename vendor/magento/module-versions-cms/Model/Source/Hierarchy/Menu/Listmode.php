<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\Source\Hierarchy\Menu;

/**
 * CMS Hierarchy Navigation Menu source model for Display list mode
 *
 */
class Listmode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            '' => __('Default'),
            '1' => __('Numbers (1, 2, 3, ...)'),
            'a' => __('Lower Alpha (a, b, c, ...)'),
            'A' => __('Upper Alpha (A, B, C, ...)'),
            'i' => __('Lower Roman (i, ii, iii, ...)'),
            'I' => __('Upper Roman (I, II, III, ...)'),
            'circle' => __('Circle'),
            'disc' => __('Disc'),
            'square' => __('Square')
        ];
    }
}
