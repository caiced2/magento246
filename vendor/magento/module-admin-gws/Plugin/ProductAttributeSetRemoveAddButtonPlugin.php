<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Catalog\Block\Adminhtml\Product\Attribute\Set\Toolbar\Main;
use Magento\Framework\View\LayoutInterface;

/**
 * Product attribute set grid block plugin
 */
class ProductAttributeSetRemoveAddButtonPlugin
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * Remove attribute set "add" button for restricted admin users
     *
     * @param Main $subject
     * @param Main $result
     * @param LayoutInterface $layout
     * @return Main
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetLayout(
        Main $subject,
        Main $result,
        LayoutInterface $layout
    ): Main {
        if (!$this->role->getIsAll()) {
            $subject->getToolbar()->unsetChild('addButton');
        }
        return $result;
    }
}
