<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Model\Plugin\Theme\Block\Html;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Theme\Block\Html\Topmenu as TopmenuBlock;

/**
 * Topmenu plugin.
 */
class Topmenu
{
    /**
     * Current customer session.
     *
     * @var Session
     */
    private $session;

    /**
     * Config with catalog permissions.
     *
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @param ConfigInterface $permissionsConfig
     * @param Session $session
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        Session $session
    ) {
        $this->permissionsConfig = $permissionsConfig;
        $this->session = $session;
    }

    /**
     * Plugin that generates unique block cache key depending on customer group.
     *
     * @param TopmenuBlock $block
     * @return null
     */
    public function beforeToHtml(TopmenuBlock $block)
    {
        if ($this->permissionsConfig->isEnabled()) {
            $customerGroupId = $this->session->getCustomerGroupId();
            $key = $block->getCacheKeyInfo();
            $key = array_values($key);
            $key[] = $customerGroupId;
            $key = implode('|', $key);
            $key = sha1($key);
            $block->setData('cache_key', $key);
        }

        return null;
    }
}
