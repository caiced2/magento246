<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller\Search;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Controller\ResultFactory;

class View extends \Magento\MultipleWishlist\Controller\Search
{
    /**
     * View customer wishlist
     *
     * @return \Magento\Framework\View\Result\Page
     * @throws NotFoundException
     */
    public function execute()
    {
        $wishlistId = $this->getRequest()->getParam('wishlist_id');
        if (!$wishlistId) {
            throw new NotFoundException(__('Page not found.'));
        }
        /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
        $wishlist = $this->_wishlistFactory->create();
        $wishlist->load($wishlistId);
        if (!$wishlist->getId()
            || !$wishlist->getVisibility()
            && $wishlist->getCustomerId() != $this->_customerSession->getCustomerId()
        ) {
            throw new NotFoundException(__('Page not found.'));
        }
        $this->_coreRegistry->register('shared_wishlist', $wishlist);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $block = $resultPage->getLayout()->getBlock('customer.wishlist.info');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }

        return $resultPage;
    }
}
