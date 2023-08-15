<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Helper;

use Magento\Invitation\Model\Invitation;

/**
 * Invitation data helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @deprecated 100.2.0 Redundant parameter
     * @var bool
     */
    protected $_isRegistrationAllowed = null;

    /**
     * Customer registration
     *
     * @var \Magento\Customer\Model\Registration
     */
    protected $registration;

    /**
     * Invitation Status
     *
     * @var \Magento\Invitation\Model\Source\Invitation\Status
     */
    protected $_invitationStatus;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Registration $registration
     * @param \Magento\Invitation\Model\Source\Invitation\Status $invitationStatus
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Registration $registration,
        \Magento\Invitation\Model\Source\Invitation\Status $invitationStatus
    ) {
        parent::__construct($context);
        $this->registration = $registration;
        $this->_invitationStatus = $invitationStatus;
    }

    /**
     * Return text for invitation status
     *
     * @param Invitation $invitation
     * @return Invitation
     */
    public function getInvitationStatusText($invitation)
    {
        return $this->_invitationStatus->getOptionText($invitation->getStatus());
    }

    /**
     * Return invitation url
     *
     * @param Invitation $invitation
     * @return string
     */
    public function getInvitationUrl($invitation)
    {
        return $this->_urlBuilder->setScope(
            $invitation->getStoreId()
        )->getUrl(
            'magento_invitation/customer_account/create',
            [
                'invitation' => $this->urlEncoder->encode($invitation->getInvitationCode()),
                '_scope_to_url' => true,
                '_nosid' => true
            ]
        );
    }

    /**
     * Return account dashboard invitation url
     *
     * @return string
     */
    public function getCustomerInvitationUrl()
    {
        return $this->_getUrl('magento_invitation/');
    }

    /**
     * Return invitation send form url
     *
     * @return string
     */
    public function getCustomerInvitationFormUrl()
    {
        return $this->_getUrl('magento_invitation/index/send');
    }

    /**
     * Checks is allowed registration in invitation controller
     *
     * @deprecated 100.2.0 Redundant method
     * @param bool $isAllowed
     * @return bool
     */
    public function isRegistrationAllowed($isAllowed = null)
    {
        if ($isAllowed === null && $this->_isRegistrationAllowed === null) {
            $result = $this->registration->isAllowed();
            if ($this->_isRegistrationAllowed === null) {
                $this->_isRegistrationAllowed = $result;
            }
        } elseif ($isAllowed !== null) {
            $this->_isRegistrationAllowed = $isAllowed;
        }

        return $this->_isRegistrationAllowed;
    }
}
