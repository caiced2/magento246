<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Controller\Adminhtml\Stub;

use Magento\AdvancedCheckout\Controller\Adminhtml\Index;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class Child extends Index
{
    public function execute()
    {
        return $this->_initData();
    }
}
