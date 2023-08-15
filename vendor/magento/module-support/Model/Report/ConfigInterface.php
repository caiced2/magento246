<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Model\Report;

/**
 * Report config interface
 *
 * @api
 */
interface ConfigInterface
{
    /**
     * Get system report groups config sorted by priority
     *
     * Returned array format:
     *  [
     *      <group_name1> => [
     *          'title' => __(<title>),
     *          'sections' => [
     *              <priority> => <class_name>,
     *              ...
     *          ],
     *          'priority' => <priority>,
     *          'data' => [...]
     *      ],
     *      ...
     *  ]
     *
     * @return array
     */
    public function getGroups();

    /**
     * Generate system report groups as options for multiselect form field
     *
     * Returned array format:
     *  [
     *      [
     *          'label' => __(<group_title1>),
     *          'value' => <group_name1>
     *      ],
     *      ...
     *  ]
     *
     * @return array
     */
    public function getGroupOptions();

    /**
     * Get supported system report groups
     *
     * Returned array format:
     *  [<group_name1>, <group_name2>, ...]
     *
     * @return array
     */
    public function getGroupNames();

    /**
     * Get system report sections by specified report groups
     *
     * Returned array format:
     *  [
     *      <class_name1>,
     *      <class_name2>,
     *      ...
     *  ]
     *
     * @param array|string $groups
     * @return array
     */
    public function getSectionNamesByGroup($groups);

    /**
     * Get section data
     *
     * @param string $section
     * @return array
     */
    public function getSectionData($section);
}
