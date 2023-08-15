<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Block\Adminhtml;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Log grid container
 *
 * @api
 * @since 100.0.2
 */
class Details extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Store curent event
     *
     * @var \Magento\Logging\Model\Event
     */
    protected $_currentEevent = null;

    /**
     * Store current event user
     *
     * @var \Magento\User\Model\User
     */
    protected $_eventUser = null;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * User model
     *
     * @var \Magento\User\Model\UserFactory
     */
    protected $_userFactory;

    /**
     * Serializer Instance
     *
     * @var Json
     */
    private $json;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\User\Model\UserFactory $userFactory
     * @param array $data
     * @param Json|null $json
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\User\Model\UserFactory $userFactory,
        array $data = [],
        Json $json = null
    ) {
        parent::__construct($context, $data);

        $this->_coreRegistry = $registry;
        $this->_userFactory = $userFactory;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Add back button
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "setLocation('" . $this->_urlBuilder->getUrl('adminhtml/*/') . "')",
                'class' => 'back'
            ]
        );
    }

    /**
     * Header text getter
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->getCurrentEvent()) {
            return __('Log Entry #%1', $this->getCurrentEvent()->getId());
        }
        return __('Log Entry Details');
    }

    /**
     * Get current event
     *
     * @return \Magento\Logging\Model\Event|null
     */
    public function getCurrentEvent()
    {
        if (null === $this->_currentEevent) {
            $this->_currentEevent = $this->_coreRegistry->registry('current_event');
        }
        return $this->_currentEevent;
    }

    /**
     * Convert x_forwarded_ip to string
     *
     * @return string|bool
     */
    public function getEventXForwardedIp()
    {
        if ($this->getCurrentEvent()) {
            $xForwarderFor = $this->getCurrentEvent()->getXForwardedIp();
            if ($xForwarderFor && $xForwarderFor != '0.0.0.0') {
                return $xForwarderFor;
            }
        }
        return false;
    }

    /**
     * Convert ip to string
     *
     * @return string|bool
     */
    public function getEventIp()
    {
        if ($this->getCurrentEvent()) {
            return $this->getCurrentEvent()->getIp();
        }
        return false;
    }

    /**
     * Replace /n => <br /> in event error_message
     *
     * @return string|bool
     */
    public function getEventError()
    {
        if ($this->getCurrentEvent()) {
            $msg = $this->getCurrentEvent()->getErrorMessage();
            return $msg !== null ? nl2br($msg) : '';
        }
        return false;
    }

    /**
     * Get current event user
     *
     * @return \Magento\User\Model\User|null
     */
    public function getEventUser()
    {
        if (null === $this->_eventUser) {
            $this->_eventUser = $this->_userFactory->create()->load($this->getUserId());
        }
        return $this->_eventUser;
    }

    /**
     * Unserialize and retrieve event info
     *
     * @return string
     */
    public function getEventInfo()
    {
        $info = null;
        $data = $this->getCurrentEvent()->getInfo();
        try {
            $info = $this->json->unserialize($data);
        } catch (\Exception $e) {
            $info = $data;
        }
        return $info;
    }
}
