<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

/**
 * Validate if string contains HTML
 */
class NoHtmlValidator
{
    /**
     * Validate if string contains HTML
     *
     * @param string $value
     * @return bool
     */
    public function validate(string $value) : bool
    {
        return $value === strip_tags($value);
    }
}
