<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Backend;

/**
 * Backend history model
 */
class History extends \Magento\GiftCardAccount\Model\History
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Backend\Model\Auth\Session $adminSession
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_adminSession = $adminSession;
        parent::__construct($context, $registry, $storeManager, $resource, $resourceCollection, $data);
    }

    /**
     * Get info about creation context
     *
     * @return \Magento\Framework\Phrase|string
     */
    protected function _getCreatedAdditionalInfo()
    {
        $info = parent::_getCreatedAdditionalInfo();
        if (empty($info)) {
            $username = $this->_getAdminUsername();
            if ($username) {
                return __('By admin: %1.', $username);
            }
        }
        return $info;
    }

    /**
     * Get info about update context
     *
     * @return \Magento\Framework\Phrase|string
     */
    protected function _getUpdatedAdditionalInfo()
    {
        $info = parent::_getUpdatedAdditionalInfo();
        if (empty($info)) {
            $username = $this->_getAdminUsername();
            if ($username) {
                return __('By admin: %1.', $username);
            }
        }
        return $info;
    }

    /**
     * Get info about sent mail context
     *
     * @return string
     */
    protected function _getSentAdditionalInfo()
    {
        $info = parent::_getSentAdditionalInfo();

        $sender = $this->_getAdminUsername();
        if ($sender) {
            $suffix = __('By admin: %1.', $sender);
            return $info ? $info . ' ' . $suffix : $suffix;
        }
        return $info;
    }

    /**
     * Get admin username
     *
     * @return string
     */
    protected function _getAdminUsername()
    {
        if ($this->_adminSession->getUser() && $this->_adminSession->getUser()->getId()) {
            $user = $this->_adminSession->getUser();
            if ($user) {
                return $user->getUsername();
            }
        }

        return '';
    }
}
