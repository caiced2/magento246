<?php
/**
 *  Converter of AdminGws configuration from \DOMDocument to tree array
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Model\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * Convert config
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        /** @var \DOMNodeList $groups */
        $groups = $source->getElementsByTagName('group');
        /** @var \DOMNode $groupConfig */
        $callbacks = [];
        $groupProcessors = [];
        foreach ($groups as $groupConfig) {
            $groupName = $groupConfig->attributes->getNamedItem('name')->nodeValue;
            $processor = $groupConfig->attributes->getNamedItem('processor');
            $groupProcessors[$groupName] = $processor ? $processor->nodeValue : null;
            /** @var $callback \DOMNode */
            foreach ($groupConfig->childNodes as $callback) {
                if ($callback->nodeType === XML_ELEMENT_NODE) {
                    $className = $callback->attributes->getNamedItem('class')->nodeValue;
                    $callbacks[$groupName][$className] = $callback->attributes->getNamedItem('method')->nodeValue;
                }
            }
        }

        /** @var \DOMNodeList $aclLevel */
        $aclLevel = $source->getElementsByTagName('level');
        /** @var \DOMNode $groupConfig */
        $rules = [];
        foreach ($aclLevel as $levelConfig) {
            $levelName = $levelConfig->attributes->getNamedItem('name')->nodeValue;
            /** @var $rule \DOMNode */
            foreach ($levelConfig->childNodes as $rule) {
                if ($rule->nodeType === XML_ELEMENT_NODE) {
                    $ruleName = $rule->attributes->getNamedItem('name')->nodeValue;
                    $rules[$levelName][$ruleName] = $rule->attributes->getNamedItem('resource')->nodeValue;
                }
            }
        }
        return ['callbacks' => $callbacks, 'acl' => $rules, 'processors' => $groupProcessors];
    }
}
