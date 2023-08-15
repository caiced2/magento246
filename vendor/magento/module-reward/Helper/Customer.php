<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Helper;

use Magento\Store\Model\Store;

/**
 * Reward Helper for operations with customer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     * @deprecated 100.2.0
     * @see $frontendUrlBuilder
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $frontendUrlBuilder;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $frontendUrlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $frontendUrlBuilder = null
    ) {
        $this->_storeManager = $storeManager;
        $this->frontendUrlBuilder = $frontendUrlBuilder ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\UrlInterface::class);
        parent::__construct($context);
    }

    /**
     * Return Unsubscribe notification URL
     *
     * @param string|bool $notification Notification type
     * @param int|string|Store $storeId
     * @return string
     */
    public function getUnsubscribeUrl($notification = false, $storeId = null)
    {
        $params = [
            '_nosid' => true
        ];

        if ($notification) {
            $params['notification'] = $notification;
        }
        if ($storeId !== null) {
            $params['store_id'] = $storeId;
        }

        return $this->frontendUrlBuilder->setScope($storeId)->getUrl('magento_reward/customer/unsubscribe', $params);
    }
}
