<?php
/**
 * Reward attribute property mapper
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\ResourceModel\Setup;

use Magento\Eav\Model\Entity\Setup\PropertyMapperAbstract;

class PropertyMapper extends PropertyMapperAbstract
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $entityTypeId;

    /**
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(\Magento\Eav\Model\Config $eavConfig)
    {
        $this->entityTypeId = $eavConfig->getEntityType('customer')->getId();
    }

    /**
     * Map customer specific attribute properties
     *
     * @param array $input
     * @param null $entityTypeId
     * @return array
     */
    public function map(array $input, $entityTypeId)
    {
        if ($entityTypeId == $this->entityTypeId) {
            return [
                'is_visible' => $this->_getValue($input, 'visible', 1),
                'is_visible_on_front' => $this->_getValue($input, 'visible_on_front', 0),
                'input_filter' => $this->_getValue($input, 'input_filter', ''),
                'lines_to_divide_multiline' => $this->_getValue($input, 'lines_to_divide', 0),
                'min_text_length' => $this->_getValue($input, 'min_text_length', 0),
                'max_text_length' => $this->_getValue($input, 'max_text_length', 0)
            ];
        }
        return [];
    }
}
