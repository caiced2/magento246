<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Wishlist rss feed block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\MultipleWishlist\Model\Plugin\Rss;

class Wishlist
{
    /**
     * @var \Magento\MultipleWishlist\Helper\Rss
     */
    protected $wishlistHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Customer\Helper\View
     */
    protected $customerViewHelper;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param \Magento\MultipleWishlist\Helper\Rss $wishlistHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Helper\View $customerViewHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\MultipleWishlist\Helper\Rss $wishlistHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Helper\View $customerViewHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->wishlistHelper = $wishlistHelper;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->customerViewHelper = $customerViewHelper;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param \Magento\Wishlist\Model\Rss\Wishlist $subject
     * @param \Closure $proceed
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetHeader(\Magento\Wishlist\Model\Rss\Wishlist $subject, \Closure $proceed)
    {
        if (!(bool)$this->scopeConfig->getValue(
            'wishlist/general/multiple_active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return $proceed();
        }

        $customer = $this->wishlistHelper->getCustomer();
        $wishlist = $this->wishlistHelper->getWishlist();
        if ($wishlist->getCustomerId() !== $customer->getId()) {
            $customer = $this->customerRepository->getById($wishlist->getCustomerId());
        }
        $customerName = $this->customerViewHelper->getCustomerName($customer);
        if ($this->wishlistHelper->isWishlistDefault($wishlist)
            && $wishlist->getName() == $this->wishlistHelper->getDefaultWishlistName()
        ) {
            $title = __("%1's Wish List", $customerName);
        } else {
            $title = __("%1's Wish List (%2)", $customerName, $wishlist->getName());
        }

        $newUrl = $this->urlBuilder->getUrl(
            'wishlist/shared/index',
            ['code' => $wishlist->getSharingCode()]
        );

        return ['title' => $title, 'description' => $title, 'link' => $newUrl, 'charset' => 'UTF-8'];
    }
}
