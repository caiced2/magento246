<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Support\Model\Report\Config\Converter as ConfigConverter;

/**
 * Report groups config model
 */
class Config implements ConfigInterface, OptionSourceInterface
{
    /**
     * Store system report group list
     *
     * @var null|array
     */
    protected $groups;

    /**
     * Modules configuration model
     *
     * @var \Magento\Support\Model\Report\Config\Data
     */
    protected $dataContainer;

    /**
     * @param \Magento\Support\Model\Report\Config\Data $dataContainer
     */
    public function __construct(\Magento\Support\Model\Report\Config\Data $dataContainer)
    {
        $this->dataContainer = $dataContainer;
    }

    /**
     * @inheritdoc
     */
    public function getGroups()
    {
        if ($this->groups === null) {
            $this->groups = $this->dataContainer->get(ConfigConverter::KEY_GROUPS);
            uasort($this->groups, function ($a, $b) {
                return $a[ConfigConverter::KEY_PRIORITY] <=> $b[ConfigConverter::KEY_PRIORITY];
            });
            array_walk(
                $this->groups,
                function (&$item) {
                    $item[ConfigConverter::KEY_TITLE] = __($item[ConfigConverter::KEY_TITLE]);
                }
            );
        }
        return $this->groups ?: [];
    }

    /**
     * Compare method used to sort system report groups
     *
     * @param mixed $a
     * @param mixed $b
     * @return bool
     *
     * @deprecated
     */
    public function groupPriorityCompare($a, $b)
    {
        return $a[ConfigConverter::KEY_PRIORITY] > $b[ConfigConverter::KEY_PRIORITY];
    }

    /**
     * @inheritdoc
     */
    public function getGroupOptions()
    {
        $groups = $this->getGroups();
        $options = [];
        foreach ($groups as $name => $config) {
            $options[] = [
                'label' => $config[ConfigConverter::KEY_TITLE],
                'value' => $name,
            ];
        }
        return $options;
    }

    /**
     * @inheritdoc
     */
    public function getGroupNames()
    {
        return array_keys($this->getGroups());
    }

    /**
     * @inheritdoc
     */
    public function getSectionNamesByGroup($groups)
    {
        if (!$groups) {
            return [];
        }
        if (!is_array($groups)) {
            $groups = array_map('trim', explode(',', $groups));
        }
        $existingGroups = $this->getGroups();
        $sections = [];
        foreach ($existingGroups as $name => $config) {
            if (!in_array($name, $groups) || empty($config[ConfigConverter::KEY_SECTIONS])) {
                continue;
            }
            $sections[] = $config[ConfigConverter::KEY_SECTIONS];
        }
        $sections = array_merge([], ...$sections);
        ksort($sections);

        return $sections;
    }

    /**
     * @inheritdoc
     */
    public function getSectionData($section)
    {
        $groups = $this->getGroups();
        foreach ($groups as $config) {
            if (isset($config[ConfigConverter::KEY_DATA][$section])) {
                return $config[ConfigConverter::KEY_DATA][$section];
            }
        }
        return [];
    }

    /**
     * Return array of options for reports to by generated
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
    public function toOptionArray()
    {
        return $this->getGroupOptions();
    }
}
