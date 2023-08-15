<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Invitation config model, used for retrieve data from configuration
 *
 */
namespace Magento\Invitation\Model;

class Config
{
    const XML_PATH_ENABLED = 'magento_invitation/general/enabled';

    const XML_PATH_ENABLED_ON_FRONT = 'magento_invitation/general/enabled_on_front';

    const XML_PATH_USE_INVITATION_MESSAGE = 'magento_invitation/general/allow_customer_message';

    const XML_PATH_MAX_INVITATION_AMOUNT_PER_SEND = 'magento_invitation/general/max_invitation_amount_per_send';

    const XML_PATH_REGISTRATION_REQUIRED_INVITATION = 'magento_invitation/general/registration_required_invitation';

    const XML_PATH_REGISTRATION_USE_INVITER_GROUP = 'magento_invitation/general/registration_use_inviter_group';

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Return max Invitation amount per send by config
     *
     * @param int $storeId
     * @return int
     */
    public function getMaxInvitationsPerSend($storeId = null)
    {
        $max = (int)$this->_scopeConfig->getValue(
            self::XML_PATH_MAX_INVITATION_AMOUNT_PER_SEND,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $max < 1 ? 1 : $max;
    }

    /**
     * Return config value for required cutomer registration by invitation
     *
     * @param int $storeId
     * @return boolean
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getInvitationRequired($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_REGISTRATION_REQUIRED_INVITATION,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return config value for use same group as inviter
     *
     * @param int $storeId
     * @return boolean
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseInviterGroup($storeId = null)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_REGISTRATION_USE_INVITER_GROUP,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check whether invitations allow to set custom message
     *
     * @param int $storeId
     * @return bool
     */
    public function isInvitationMessageAllowed($storeId = null)
    {
        return (bool)$this->_scopeConfig->isSetFlag(
            self::XML_PATH_USE_INVITATION_MESSAGE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve configuration for availability of invitations
     * on global level. Also will disallowe any functionality in admin.
     *
     * @param int $storeId
     * @return boolean
     */
    public function isEnabled($storeId = null)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve configuration for availability of invitations
     * on front for specified store. Global parameter 'enabled' has more priority.
     *
     * @param int $storeId
     * @return boolean
     */
    public function isEnabledOnFront($storeId = null)
    {
        if ($this->isEnabled($storeId)) {
            return $this->_scopeConfig->isSetFlag(
                self::XML_PATH_ENABLED_ON_FRONT,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return false;
    }
}
