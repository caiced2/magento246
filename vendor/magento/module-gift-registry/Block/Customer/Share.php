<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Customer gift registry share block
 *
 * @api
 * @since 100.0.2
 */
class Share extends \Magento\Customer\Block\Account\Dashboard
{
    /**
     * @var mixed
     */
    protected $_formData = null;

    /**
     * Gift registry data
     *
     * @var \Magento\GiftRegistry\Helper\Data
     */
    protected $_giftRegistryData = null;

    /**
     * Customer view helper
     *
     * @var \Magento\Customer\Helper\View
     */
    protected $_customerView;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     * @param \Magento\Customer\Helper\View $customerView
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        \Magento\GiftRegistry\Helper\Data $giftRegistryData,
        \Magento\Customer\Helper\View $customerView,
        array $data = []
    ) {
        $this->_giftRegistryData = $giftRegistryData;
        $this->_customerView = $customerView;
        parent::__construct(
            $context,
            $customerSession,
            $subscriberFactory,
            $customerRepository,
            $customerAccountManagement,
            $data
        );
    }

    /**
     * Retrieve form header
     *
     * @return \Magento\Framework\Phrase
     */
    public function getFormHeader()
    {
        $formHeader = $this->getEntity()->getTitle();
        return $this->escapeHtml(__("Share '%1' Gift Registry", $formHeader));
    }

    /**
     * Retrieve escaped customer name
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getCustomerName()
    {
        return $this->escapeHtml($this->_customerView->getCustomerName($this->getCustomer()));
    }

    /**
     * Retrieve escaped customer email
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getCustomerEmail()
    {
        return $this->escapeHtml($this->getCustomer()->getEmail());
    }

    /**
     * Retrieve recipients config limit
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getRecipientsLimit()
    {
        return (int)$this->_giftRegistryData->getRecipientsLimit();
    }

    /**
     * Retrieve entered data by key
     *
     * @param string $key
     * @return string|null
     */
    public function getFormData($key)
    {
        if ($this->_formData === null) {
            $this->_formData = $this->customerSession->getData('sharing_form', true);
        }
        if (!$this->_formData || !isset($this->_formData[$key])) {
            return null;
        } else {
            return $this->escapeHtml($this->_formData[$key]);
        }
    }

    /**
     * Return back url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getBackUrl()
    {
        return $this->getUrl('magento_giftregistry');
    }

    /**
     * Return form send url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getSendUrl()
    {
        return $this->getUrl('magento_giftregistry/index/send', ['id' => $this->getEntity()->getId()]);
    }
}
