<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminSessionLoginSuccessObserver implements ObserverInterface
{
    /**
     * @var AdminLogin
     */
    protected $adminLogin;

    /**
     * @param AdminLogin $adminLogin
     */
    public function __construct(
        \Magento\Logging\Observer\AdminLogin $adminLogin
    ) {
        $this->adminLogin = $adminLogin;
    }

    /**
     * Log successful admin sign in
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->adminLogin->logAdminLogin($observer->getUser()->getUsername(), $observer->getUser()->getId());
    }
}
