<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model\Search\Strategy;

/**
 * Wishlist search by name and last name strategy
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Name implements \Magento\MultipleWishlist\Model\Search\Strategy\StrategyInterface
{
    /**
     * Customer firstname provided for search
     *
     * @var string
     */
    protected $_firstname;

    /**
     * Customer lastname provided for search
     *
     * @var string
     */
    protected $_lastname;

    /**
     * Customer collection factory
     *
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * Construct
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->_customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Validate search params
     *
     * @param array $params
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setSearchParams(array $params)
    {
        if (empty($params['firstname']) || strlen($params['firstname']) < 2) {
            throw new \InvalidArgumentException(__('Please enter at least 2 letters of the first name.'));
        }
        $this->_firstname = $params['firstname'];
        if (empty($params['lastname']) || strlen($params['lastname']) < 2) {
            throw new \InvalidArgumentException(__('Please enter at least 2 letters of the last name.'));
        }
        $this->_lastname = $params['lastname'];
    }

    /**
     * Filter wishlist collection
     *
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $collection
     * @return \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection
     */
    public function filterCollection(\Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $collection)
    {
        /* @var $customers \Magento\Customer\Model\ResourceModel\Customer\Collection */
        $customers = $this->_customerCollectionFactory->create();
        $customers->addAttributeToFilter(
            [['attribute' => 'firstname', 'like' => '%' . $this->_firstname . '%']]
        )->addAttributeToFilter(
            [['attribute' => 'lastname', 'like' => '%' . $this->_lastname . '%']]
        );

        $collection->filterByCustomerIds($customers->getAllIds());
        foreach ($collection as $wishlist) {
            $wishlist->setCustomer($customers->getItemById($wishlist->getCustomerId()));
        }
        return $collection;
    }
}
