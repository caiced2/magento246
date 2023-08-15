<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Invitation\Block\Adminhtml\Invitation\View\Tab;

use Magento\Customer\Api\GroupRepositoryInterface as CustomerGroupRepository;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Invitation view general tab block
 *
 */
class General extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'view/tab/general.phtml';

    /**
     * Invitation data
     *
     * @var \Magento\Invitation\Helper\Data
     */
    protected $_invitationData;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Customer Factory
     *
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CustomerGroupRepository
     */
    protected $customerGroupRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Invitation\Helper\Data $invitationData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param CustomerGroupRepository $customerGroupRepository
     * @param array $data
     * @param \Magento\Invitation\Helper\Data|null $invitationHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Invitation\Helper\Data $invitationData,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        CustomerGroupRepository $customerGroupRepository,
        array $data = [],
        \Magento\Invitation\Helper\Data $invitationHelper = null
    ) {
        $data['invitationHelper'] = $invitationHelper ?? ObjectManager::getInstance()
                ->get(\Magento\Invitation\Helper\Data::class);
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->_invitationData = $invitationData;
        $this->_customerFactory = $customerFactory;
        $this->customerGroupRepository = $customerGroupRepository;
    }

    /**
     * Tab label getter
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('General');
    }

    /**
     * Tab Title getter
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Check whether tab can be showed
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check whether tab is hidden
     *
     * @return false
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Return Invitation for view
     *
     * @return \Magento\Invitation\Model\Invitation
     */
    public function getInvitation()
    {
        return $this->_coreRegistry->registry('current_invitation');
    }

    /**
     * Check whether it is possible to edit invitation message
     *
     * @return bool
     */
    public function canEditMessage()
    {
        return $this->getInvitation()->canMessageBeUpdated();
    }

    /**
     * Return save message button html
     *
     * @return string
     */
    public function getSaveMessageButtonHtml()
    {
        return $this->getChildHtml('save_message_button');
    }

    /**
     * Return invitation customer model
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getReferral()
    {
        if (!$this->hasData('referral')) {
            if ($this->getInvitation()->getReferralId()) {
                $referral = $this->_customerFactory->create()->load($this->getInvitation()->getReferralId());
            } else {
                $referral = false;
            }

            $this->setData('referral', $referral);
        }

        return $this->getData('referral');
    }

    /**
     * Return invitation customer model
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        if (!$this->hasData('customer')) {
            if ($this->getInvitation()->getCustomerId()) {
                $customer = $this->_customerFactory->create()->load($this->getInvitation()->getCustomerId());
            } else {
                $customer = false;
            }

            $this->setData('customer', $customer);
        }

        return $this->getData('customer');
    }

    /**
     * Return customer group code by group id
     * If $configUsed passed as true then result will be default string
     * instead of N/A sign
     *
     * @param int $groupId
     * @param bool $configUsed
     * @return \Magento\Framework\Phrase|string
     */
    public function getCustomerGroupCode($groupId, $configUsed = false)
    {
        try {
            $group = $this->customerGroupRepository->getById($groupId);
            return $group->getCode();
        } catch (NoSuchEntityException $e) {
            if ($configUsed) {
                return __('Default from System Configuration');
            } else {
                return __('N/A');
            }
        }
    }

    /**
     * Invitation website name getter
     *
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->_storeManager->getStore($this->getInvitation()->getStoreId())->getWebsite()->getName();
    }

    /**
     * Invitation store name getter
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore($this->getInvitation()->getStoreId())->getName();
    }

    /**
     * Get invitation URL in case if it can be accepted
     *
     * @return string|false
     */
    public function getInvitationUrl()
    {
        if (!$this->getInvitation()->canBeAccepted(
            $this->_storeManager->getStore($this->getInvitation()->getStoreId())->getWebsiteId()
        )
        ) {
            return false;
        }
        return $this->_invitationData->getInvitationUrl($this->getInvitation());
    }

    /**
     * Checks if this invitation was sent by admin
     *
     * @return boolean - true if this invitation was sent by admin, false otherwise
     */
    public function isInvitedByAdmin()
    {
        $invitedByAdmin = $this->getInvitation()->getCustomerId() == null;
        return $invitedByAdmin;
    }

    /**
     * Check whether can show referral link
     *
     * @return bool
     */
    public function canShowReferralLink()
    {
        return $this->_authorization->isAllowed('Magento_Customer::manage');
    }
}
