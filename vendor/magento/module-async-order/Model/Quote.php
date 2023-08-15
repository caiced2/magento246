<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AsyncOrder\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Initial async quote model.
 */
class Quote extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'async_sales_quote';

    /**
     * @var string
     */
    protected $_eventObject = 'async_quote';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\AsyncOrder\Model\ResourceModel\Quote::class);
    }
}
