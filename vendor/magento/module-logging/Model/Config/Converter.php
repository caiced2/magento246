<?php
/**
 * Logging configuration Converter
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Model\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        $result = ['logging' => []];
        $xpath = new \DOMXPath($source);
        $result['logging']['actions'] = $this->_getActionTitles($xpath);

        $groups = $xpath->query('/logging/groups/group');
        /** @var \DOMNode $group */
        foreach ($groups as $group) {
            $groupId = $group->attributes->getNamedItem('name')->nodeValue;
            $result['logging'][$groupId] = $this->_convertGroup($group, $groupId);
        }

        return $result;
    }

    /**
     * Retrieves titles array from Xpath object
     *
     * @param \DOMXPath $xpath
     * @return array
     */
    protected function _getActionTitles($xpath)
    {
        $result = [];
        $actions = $xpath->query('/logging/actions/action');

        /** @var \DOMNode $action */
        foreach ($actions as $action) {
            $actionId = $action->attributes->getNamedItem('id')->nodeValue;
            foreach ($action->childNodes as $label) {
                if ($label->nodeName == 'label') {
                    $result[$actionId]['label'] = $label->nodeValue;
                }
            }
        }
        return $result;
    }

    /**
     * Convert Group node to array
     *
     * @param \DOMNode $group
     * @param string $groupId
     * @return array
     */
    protected function _convertGroup($group, $groupId)
    {
        $result = [];
        foreach ($group->childNodes as $groupParams) {
            switch ($groupParams->nodeName) {
                case 'label':
                    $result['label'] = $groupParams->nodeValue;
                    break;
                case 'expected_models':
                    $result['expected_models'] = $this->_convertExpectedModels($groupParams);
                    break;
                case 'events':
                    $result['actions'] = $this->_convertEvents($groupParams, $groupId);
                    break;
            }
        }
        return $result;
    }

    /**
     * Convert Event node to array
     *
     * @param \DOMNode $event
     * @param string $groupId
     * @return array
     */
    protected function _convertEvent($event, $groupId)
    {
        $result = ['group_name' => $groupId];
        $eventAttributes = $event->attributes;
        $actionAliasAttribute = $eventAttributes->getNamedItem('action_alias');
        if ($actionAliasAttribute !== null) {
            $result['action'] = $actionAliasAttribute->nodeValue;
        }

        $postDispatch = $eventAttributes->getNamedItem('post_dispatch');
        $result['controller_action'] = $eventAttributes->getNamedItem('controller_action')->nodeValue;
        if ($postDispatch !== null) {
            $result['post_dispatch'] = $postDispatch->nodeValue;
        }
        foreach ($event->childNodes as $eventData) {
            switch ($eventData->nodeName) {
                case 'expected_models':
                    $result['expected_models'] = $this->_convertExpectedModels($eventData);
                    break;
                case 'skip_on_back':
                    $result['skip_on_back'] = $this->_convertSkipOnBack($eventData);
                    break;
            }
        }
        return $result;
    }

    /**
     * Convert events grouping node
     *
     * @param \DOMNode $events
     * @param string $groupId
     * @return array
     */
    protected function _convertEvents($events, $groupId)
    {
        $result = [];
        foreach ($events->childNodes as $event) {
            if ($event->nodeName == 'event') {
                $result[$event->attributes->getNamedItem(
                    'controller_action'
                )->nodeValue] = $this->_convertEvent(
                    $event,
                    $groupId
                );
            }
        }
        return $result;
    }

    /**
     * Convert skip_on_back node to array
     *
     * @param \DOMNode $skipOnBack
     * @return array
     */
    protected function _convertSkipOnBack($skipOnBack)
    {
        $result = [];
        foreach ($skipOnBack->childNodes as $controllerAction) {
            if ($controllerAction->nodeName == 'controller_action') {
                $result[] = $controllerAction->attributes->getNamedItem('name')->nodeValue;
            }
        }
        return $result;
    }

    /**
     * Convert expected_models grouping node
     *
     * @param \DOMNode $expectedModels
     * @return array
     */
    protected function _convertExpectedModels($expectedModels)
    {
        $result = [];
        foreach ($expectedModels->childNodes as $expectedModelNode) {
            if ($expectedModelNode->nodeName == 'expected_model') {
                $result[$expectedModelNode->attributes->getNamedItem(
                    'class'
                )->nodeValue] = $this->_convertExpectedModel(
                    $expectedModelNode
                );
            }
        }
        $extendsGroup = $expectedModels->attributes->getNamedItem('merge_group');
        if ($extendsGroup !== null && $extendsGroup->nodeValue == 'true') {
            $result['@']['extends'] = 'merge';
        }
        return $result;
    }

    /**
     * Convert Expected Model node to array
     *
     * @param \DOMNode $expectedModel
     * @return array
     */
    protected function _convertExpectedModel($expectedModel)
    {
        $result = [];
        foreach ($expectedModel->childNodes as $parameter) {
            switch ($parameter->nodeName) {
                case 'skip_fields':
                    $result['skip_data'] = $this->_convertSkipFields($parameter);
                    break;
                case 'additional_fields':
                    $result['additional_data'] = $this->_convertAdditionalFields($parameter);
            }
        }
        return $result;
    }

    /**
     * Convert skip_fields node to array
     *
     * @param \DOMNode $skipFields
     * @return array
     */
    protected function _convertSkipFields($skipFields)
    {
        $result = [];
        foreach ($skipFields->childNodes as $skipField) {
            if ($skipField->nodeName == 'field') {
                $result[] = $skipField->attributes->getNamedItem('name')->nodeValue;
            }
        }
        return $result;
    }

    /**
     * Convert additional_fields node to array
     *
     * @param \DOMNode $additionalFields
     * @return array
     */
    protected function _convertAdditionalFields($additionalFields)
    {
        $result = [];
        foreach ($additionalFields->childNodes as $additionalField) {
            if ($additionalField->nodeName == 'field') {
                $result[] = $additionalField->attributes->getNamedItem('name')->nodeValue;
            }
        }
        return $result;
    }
}
