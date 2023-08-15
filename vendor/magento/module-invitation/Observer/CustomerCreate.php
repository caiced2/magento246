<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Invitation\Controller\Customer\Account\CreatePost;
use Magento\Invitation\Model\InvitationProvider;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Customer Create Observer
 */
class CustomerCreate implements ObserverInterface
{
    /**
     * @var InvitationProvider
     */
    private $invitationProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * CustomerCreate constructor.
     *
     * @param InvitationProvider $invitationProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        InvitationProvider $invitationProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->invitationProvider = $invitationProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * Process invitation after customer accepted invitation and signed up account
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $controller = $event->getData('account_controller');
        if ($controller instanceof CreatePost) {
            $customerId = $event->getData('customer')->getId();
            if ($customerId) {
                try {
                    $invitation = $this->invitationProvider->get($controller->getRequest());
                    $invitation->accept($this->storeManager->getWebsite()->getId(), $customerId);
                } catch (\Exception $exception) {
                    // cannot accept invitation
                }
            }
        }
    }
}
