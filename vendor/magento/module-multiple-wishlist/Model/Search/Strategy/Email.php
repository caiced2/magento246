<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model\Search\Strategy;

use Magento\Framework\Validator\EmailAddress;
use Magento\Framework\Validator\ValidatorChain;

/**
 * Wishlist search by email strategy
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Email implements \Magento\MultipleWishlist\Model\Search\Strategy\StrategyInterface
{
    /**
     * Email provided for search
     *
     * @var string
     */
    protected $_email;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Construct
     *
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * Set search fields required by search strategy
     *
     * @param array $params
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setSearchParams(array $params)
    {
        if (empty($params['email']) || !ValidatorChain::is($params['email'], EmailAddress::class)) {
            throw new \InvalidArgumentException(__('Please enter a valid email address.'));
        }
        $this->_email = $params['email'];
    }

    /**
     * Filter given wishlist collection
     *
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $collection
     * @return \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection
     */
    public function filterCollection(\Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $collection)
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($this->_storeManager->getStore()->getWebsiteId())->loadByEmail($this->_email);

        $collection->filterByCustomerId($customer->getId());
        foreach ($collection as $item) {
            $item->setCustomer($customer);
        }
        return $collection;
    }
}
