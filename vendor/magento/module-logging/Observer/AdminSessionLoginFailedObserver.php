<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Observer;

use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Event\ObserverInterface;

class AdminSessionLoginFailedObserver implements ObserverInterface
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
     * Log failure of sign in
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $eventModel = $this->adminLogin->logAdminLogin($observer->getUserName());

        if (class_exists(\Magento\User\Model\Backend\Observer::class, false) && $eventModel) {
            $exception = $observer->getException();
            if ($exception instanceof UserLockedException) {
                $eventModel->setInfo(__('This user is locked'))->save();
            }
        }
    }
}
