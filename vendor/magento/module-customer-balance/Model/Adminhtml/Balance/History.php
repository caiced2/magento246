<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Adminhtml\Balance;

/**
 * Customerbalance history model for adminhtml area
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class History extends \Magento\CustomerBalance\Model\Balance\History
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param \Magento\Customer\Helper\View $customerHelperView
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Customer\Helper\View $customerHelperView,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_authSession = $authSession;
        parent::__construct(
            $context,
            $registry,
            $transportBuilder,
            $storeManager,
            $design,
            $scopeConfig,
            $customerRegistry,
            $customerHelperView,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Add information about admin user who changed customer balance
     *
     * @return $this
     */
    public function beforeSave()
    {
        $balance = $this->getBalanceModel();
        if (in_array((int)$balance->getHistoryAction(), [self::ACTION_CREATED, self::ACTION_UPDATED])
            && !$balance->getUpdatedActionAdditionalInfo()
        ) {
            $user = $this->_authSession->getUser();
            if ($user && $user->getUsername()) {
                if ($balance->getComment() === null || !trim($balance->getComment())) {
                    $this->setAdditionalInfo(__('By admin: %1.', $user->getUsername()));
                } else {
                    $this->setAdditionalInfo(__('By admin: %1. (%2)', $user->getUsername(), $balance->getComment()));
                }
            }
        }

        return parent::beforeSave();
    }
}
