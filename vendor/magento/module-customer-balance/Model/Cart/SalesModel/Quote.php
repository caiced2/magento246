<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Cart\SalesModel;

/**
 * CustomerBalance adapter for \Magento\Quote\Model\Quote sales model
 */
class Quote extends \Magento\Payment\Model\Cart\SalesModel\Quote
{
    /**
     * Overwrite for specific data key
     *
     * @param string $key
     * @param mixed $args
     * @return mixed
     */
    public function getDataUsingMethod($key, $args = null)
    {
        if ($key == 'customer_balance_base_amount') {
            $key = 'base_customer_bal_amount_used';
        }
        return parent::getDataUsingMethod($key, $args);
    }
}
