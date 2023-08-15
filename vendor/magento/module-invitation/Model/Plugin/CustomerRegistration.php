<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Model\Plugin;

use Magento\Customer\Model\Registration;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Invitation\Helper\Data;
use Magento\Invitation\Model\Config;
use Magento\Invitation\Model\InvitationProvider;

class CustomerRegistration
{
    /**
     * @var Config
     */
    protected $_invitationConfig;

    /**
     * @deprecated 100.2.0 Redundant parameter
     * @var Data
     */
    protected $_invitationHelper;

    /**
     * @var InvitationProvider
     */
    private $invitationProvider;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Config $invitationConfig
     * @param Data $invitationHelper
     * @param InvitationProvider|null $invitationProvider
     * @param RequestInterface|null $request
     */
    public function __construct(
        Config $invitationConfig,
        Data $invitationHelper,
        InvitationProvider $invitationProvider = null,
        RequestInterface $request = null
    ) {
        $this->_invitationConfig = $invitationConfig;
        $this->_invitationHelper = $invitationHelper;
        $this->invitationProvider = $invitationProvider
            ?: ObjectManager::getInstance()->get(InvitationProvider::class);
        $this->request = $request
            ?: ObjectManager::getInstance()->get(RequestInterface::class);
    }

    /**
     * Check if registration is allowed
     *
     * Registration disallows for invalid invitations only if 'Enable Invitations Functionality' value
     * is equal to 'Yes' and 'New Accounts Registration' value is equal to 'By Invitation Only'.
     *
     * @param Registration $subject
     * @param boolean $invocationResult
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsAllowed(Registration $subject, $invocationResult)
    {
        if ($invocationResult
            && $this->_invitationConfig->isEnabled()
            && $this->_invitationConfig->getInvitationRequired()
        ) {
            try {
                $invitation = $this->invitationProvider->get($this->request);
                if (!$invitation->getId()) {
                    $invocationResult = false;
                }
            } catch (\Exception $e) {
                $invocationResult = false;
            }
        }
        return $invocationResult;
    }
}
