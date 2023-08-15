<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\Page;
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistHelper;
use Magento\Wishlist\Controller\Index\Index as WishlistIndex;

/**
 * Multiple wish list page
 */
class Index extends WishlistIndex implements HttpGetActionInterface
{
    /**
     * Display customer wishlist
     *
     * @return ResultInterface
     *
     * @throws NotFoundException
     */
    public function execute()
    {
        /* @var MultipleWishlistHelper $helper */
        $helper = $this->_objectManager->get(MultipleWishlistHelper::class);

        if (!$helper->isMultipleEnabled()) {
            $wishlistId = $this->getRequest()->getParam('wishlist_id');

            if ($wishlistId && $wishlistId != $helper->getDefaultWishlist()->getId()) {
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($helper->getListUrl());

                return $resultRedirect;
            } else {
                $page = parent::execute();
            }
        } else {
            /** @var Page $page */
            $page = parent::execute();
            $page->getConfig()->addBodyClass('page-multiple-wishlist');
        }

        return $page;
    }
}
