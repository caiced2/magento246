<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Model;

use Magento\Wishlist\Model\AuthenticationStateInterface;

class WishlistAuthenticationState implements AuthenticationStateInterface
{
    /**
     * @var \Magento\PersistentHistory\Helper\Data
     */
    protected $phHelper;

    /**
     * @var \Magento\Persistent\Helper\Session
     */
    protected $persistentSession;

    /**
     * @param \Magento\Persistent\Helper\Session $persistentSession
     * @param \Magento\PersistentHistory\Helper\Data $phHelper
     */
    public function __construct(
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\PersistentHistory\Helper\Data $phHelper
    ) {
        $this->persistentSession = $persistentSession;
        $this->phHelper = $phHelper;
    }

    /**
     * Is authentication enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (!$this->persistentSession->isPersistent() || !$this->phHelper->isWishlistPersist());
    }
}
