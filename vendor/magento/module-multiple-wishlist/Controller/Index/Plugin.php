<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\RequestInterface;

class Plugin
{
    /**
     * @var \Magento\MultipleWishlist\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\MultipleWishlist\Helper\Data $helper
     */
    public function __construct(\Magento\MultipleWishlist\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Check whether multiple wishlist feature is enabled
     *
     * @param \Magento\MultipleWishlist\Controller\IndexInterface $subject
     * @param RequestInterface $request
     * @return void
     * @throws \Magento\Framework\Exception\NotFoundException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        \Magento\MultipleWishlist\Controller\IndexInterface $subject,
        RequestInterface $request
    ) {
        if (!$this->helper->isMultipleEnabled()) {
            throw new NotFoundException(__('Page not found.'));
        }
    }
}
