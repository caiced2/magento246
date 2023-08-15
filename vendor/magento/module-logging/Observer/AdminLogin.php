<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Observer;

class AdminLogin
{
    /**
     * @var \Magento\Logging\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\User\Model\User
     */
    protected $_user;

    /**
     * @var \Magento\Logging\Model\Event
     */
    protected $_event;

    /**
     * Request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;

    /**
     * @param \Magento\Logging\Model\Config $config
     * @param \Magento\User\Model\User $user
     * @param \Magento\Logging\Model\Event $event
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     */
    public function __construct(
        \Magento\Logging\Model\Config $config,
        \Magento\User\Model\User $user,
        \Magento\Logging\Model\Event $event,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
    ) {
        $this->_config = $config;
        $this->_user = $user;
        $this->_event = $event;
        $this->_request = $request;
        $this->_remoteAddress = $remoteAddress;
    }

    /**
     * Log sign in attempt
     *
     * @param string $username
     * @param int $userId
     * @return \Magento\Logging\Model\Event
     */
    public function logAdminLogin($username, $userId = null)
    {
        $eventCode = 'admin_login';
        if (!$this->_config->isEventGroupLogged($eventCode)) {
            return;
        }
        $success = (bool)$userId;
        if (!$userId) {
            $userId = $this->_user->loadByUsername($username)->getId();
        }
        $this->_event->setData(
            [
                'ip' => $this->_remoteAddress->getRemoteAddress(),
                'user' => $username,
                'user_id' => $userId,
                'is_success' => $success,
                'fullaction' => "{$this->_request->getRouteName()}_{$this->_request->getControllerName()}" .
                    "_{$this->_request->getActionName()}",
                'event_code' => $eventCode,
                'action' => 'login',
            ]
        );

        return $this->_event->save();
    }
}
