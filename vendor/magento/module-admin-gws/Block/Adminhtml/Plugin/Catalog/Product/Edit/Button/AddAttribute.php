<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Block\Adminhtml\Plugin\Catalog\Product\Edit\Button;

use Magento\AdminGws\Model\Role;

/**
 * Class AddAttribute plugin of Magento\Catalog\Block\Adminhtml\Product\Edit\Button\AddAttribute
 */
class AddAttribute
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
     * @param \Magento\Catalog\Block\Adminhtml\Product\Edit\Button\AddAttribute $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetButtonData(
        \Magento\Catalog\Block\Adminhtml\Product\Edit\Button\AddAttribute $subject,
        $result
    ) {
        if ($this->role->getIsAll()) {
            return $result;
        } else {
            return [];
        }
    }
}
