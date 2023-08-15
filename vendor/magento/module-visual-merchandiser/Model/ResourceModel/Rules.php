<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\ResourceModel;

/**
 * Class Rules
 * @package Magento\VisualMerchandiser\Model\ResourceModel
 * @api
 * @since 100.0.2
 */
class Rules extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize vm rules model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('visual_merchandiser_rule', 'rule_id');
    }
}
