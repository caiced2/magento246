<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Invitation\Model;

/**
 * Class \Magento\Invitation\Model\Logging
 *
 * Model for logging event related to Invitation, active only if Magento_Logging module is enabled
 */
class Logging
{
    /**
     * Flag that indicates customer registration page
     *
     * @var boolean
     */
    protected $_flagInCustomerRegistration = false;

    /**
     * Invitation configuration
     *
     * @var \Magento\Invitation\Model\Config
     */
    protected $_config;

    /**
     * Invitation data
     *
     * @var \Magento\Invitation\Helper\Data
     */
    protected $_invitationData;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @param \Magento\Invitation\Helper\Data $invitationData
     * @param \Magento\Invitation\Model\Config $config
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Invitation\Helper\Data $invitationData,
        \Magento\Invitation\Model\Config $config,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_invitationData = $invitationData;
        $this->_config = $config;
        $this->messageManager = $messageManager;
        $this->_request = $request;
    }

    /**
     * Handler for invitation mass update
     *
     * @param array $config
     * @param \Magento\Logging\Model\Event $eventModel
     * @return \Magento\Logging\Model\Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchInvitationMassUpdate($config, $eventModel)
    {
        $messages = $this->messageManager->getMessages();
        $errors = $messages->getErrors();
        $notices = $messages->getItemsByType(\Magento\Framework\Message\MessageInterface::TYPE_NOTICE);
        $isSuccess = empty($errors) && empty($notices);
        return $eventModel->setIsSuccess($isSuccess)->setInfo($this->_request->getParam('invitations'));
    }
}
