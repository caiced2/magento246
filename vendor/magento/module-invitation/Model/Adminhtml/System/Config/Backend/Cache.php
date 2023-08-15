<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Invitation backend config cache model
 *
 */
namespace Magento\Invitation\Model\Adminhtml\System\Config\Backend;

use Magento\Framework\DataObject\IdentityInterface;

class Cache extends \Magento\Config\Model\Config\Backend\Cache implements IdentityInterface
{
    /**
     * Cache tags to clean
     *
     * @var string[]
     */
    protected $_cacheTags = [\Magento\Backend\Block\Menu::CACHE_TAGS];

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Magento\Backend\Block\Menu::CACHE_TAGS];
    }
}
