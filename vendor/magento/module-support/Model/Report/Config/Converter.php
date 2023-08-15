<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Config;

/**
 * Converts supported report groups from \DOMDocument to array
 */
class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**#@+
     * Array keys for config internal representation.
     */
    const KEY_GROUPS = 'groups';
    const KEY_TITLE = 'title';
    const KEY_SECTIONS = 'sections';
    const KEY_PRIORITY = 'priority';
    const KEY_DATA = 'data';
    /**#@-*/

    /**
     * Convert data to array type
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        return [self::KEY_GROUPS => $this->getGroups($source)];
    }

    /**
     * Extract supported report groups configuration from XML config
     *
     * @param \DOMDocument $source
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getGroups(\DOMDocument $source)
    {
        $result = [];

        /** @var \DOMNodeList $reportGroups */
        $reportGroups = $source->getElementsByTagName('group');

        /** @var \DOMElement $reportGroup */
        foreach ($reportGroups as $reportGroup) {
            $groupName = $reportGroup->getAttribute('name');
            if (!$groupName) {
                throw new \InvalidArgumentException('Attribute "name" of one of "group"s does not exist');
            }

            /** @var \DOMElement $groupSections */
            $groupSections = $reportGroup->getElementsByTagName('sections')->item(0);
            if (!$groupSections) {
                throw new \InvalidArgumentException('Tag "sections" of one of "group"s does not exist');
            }

            $sections = [];
            $sectionData = [];

            /** @var \DOMElement $section */
            foreach ($groupSections->getElementsByTagName('section') as $section) {
                $model = trim($section->getAttribute('model'));
                $sectionPriority = (int)$section->getAttribute('priority');
                $sections[$sectionPriority] = $model;
                $currentSectionData = [];
                /** @var \DOMElement $data */
                $data = $section->getElementsByTagName('data')->item(0);
                if ($data) {
                    /** @var \DOMElement $item */
                    foreach ($data->getElementsByTagName('item') as $item) {
                        $currentSectionData[$item->getAttribute('name')] = $item->firstChild->nodeValue;
                    }
                }
                $sectionData[$model] = $currentSectionData;
            }

            $groupPriority = (int)$reportGroup->getAttribute('priority');

            /** @var \DOMElement $title */
            $title = $reportGroup->getElementsByTagName('title')->item(0);

            $result[$groupName] = [
                self::KEY_TITLE => $title->firstChild->nodeValue,
                self::KEY_SECTIONS => $sections,
                self::KEY_PRIORITY => $groupPriority,
                self::KEY_DATA => $sectionData
            ];
        }
        return $result;
    }
}
