<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog event backend config cache model
 *
 */
namespace Magento\CatalogEvent\Model\Adminhtml\System\Config\Backend;

use Magento\Backend\Block\Menu;
use Magento\Config\Model\Config\Backend\Cache as BackendCache;

class Cache extends BackendCache implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Cache tags to clean
     *
     * @var string[]
     */
    protected $_cacheTags = [Menu::CACHE_TAGS];

    /**
     * Get identities
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getIdentities()
    {
        return [Menu::CACHE_TAGS];
    }
}
