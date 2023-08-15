<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class Index extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Invitation::magento_invitation';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Invitation Factory
     *
     * @var \Magento\Invitation\Model\InvitationFactory
     */
    protected $_invitationFactory;

    /**
     * Invitation Config
     *
     * @var \Magento\Invitation\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Invitation\Model\InvitationFactory $invitationFactory
     * @param \Magento\Invitation\Model\Config $config
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Invitation\Model\InvitationFactory $invitationFactory,
        \Magento\Invitation\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $coreRegistry;
        $this->_invitationFactory = $invitationFactory;
        $this->_config = $config;
        parent::__construct($context);
    }

    /**
     * Init invitation model by request
     *
     * @return \Magento\Invitation\Model\Invitation
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initInvitation()
    {
        $invitation = $this->_invitationFactory->create()->load($this->getRequest()->getParam('id'));
        if (!$invitation->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t find this invitation.'));
        }
        $this->_coreRegistry->register('current_invitation', $invitation);

        return $invitation;
    }

    /**
     * Acl admin user check
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_config->isEnabled() && $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
