<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Model;

/**
 * Enterprise banner catalog rule model
 *
 * @method int getBannerId()
 * @method \Magento\Banner\Model\Catalogrule setBannerId(int $value)
 * @method int getRuleId()
 * @method \Magento\Banner\Model\Catalogrule setRuleId(int $value)
 */
class Catalogrule extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize promo catalog price rule model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Banner\Model\ResourceModel\Catalogrule::class);
    }
}
