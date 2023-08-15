<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Logging helper
 */
namespace Magento\Logging\Helper;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Logging\Block\Adminhtml\Grid\Column\Ip;
use Magento\Logging\Model\ResourceModel\Event\Collection;

class Data extends \Magento\Framework\App\Helper\AbstractHelper implements ArgumentInterface
{
    /**
     * @var \Magento\Logging\Model\Config
     */
    protected $_config;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Logging\Model\Config $config
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context, \Magento\Logging\Model\Config $config)
    {
        $this->_config = $config;
        parent::__construct($context);
    }

    /**
     * Join array into string except empty values
     *
     * @param array $array Array to join
     * @param string $glue Separator to join
     * @return string
     */
    public function implodeValues($array, $glue = ', ')
    {
        if (!is_array($array)) {
            return $array;
        }
        $result = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $result[] = $this->implodeValues($item);
            } else {
                if ((string)$item !== '') {
                    $result[] = $item;
                }
            }
        }
        return implode($glue, $result);
    }

    /**
     * Get translated label by logging action name
     *
     * @param string $action
     * @return string
     */
    public function getLoggingActionTranslatedLabel($action)
    {
        return $this->_config->getActionLabel($action);
    }

    /**
     * Filter IP callback
     *
     * @param Collection $collection
     * @param Ip $column
     */
    public function filterIPv6(Collection $collection, Column $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $condition = $column->getFilter()->getCondition();
        if (!is_numeric($condition) && !isset($condition['ntoa'])) {
            $field = 'info';
        }

        $collection->addFieldToFilter($field, $condition);
    }
}
