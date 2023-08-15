<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\CustomerCustomAttributes;

use Magento\AdminGws\Model\Role;
use Magento\CustomerCustomAttributes\Block\Adminhtml\Customer\Attribute\Edit;
use Magento\Framework\View\LayoutInterface;

/**
 * Product attribute set grid block plugin
 */
class CustomerAttributeEditRemoveButtonsPlugin
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
     * Remove customer attribute Edit "Save, Save And Edit, and Reset" button for restricted admin users
     *
     * @param Edit $subject
     * @param Edit $result
     * @param LayoutInterface $layout
     * @return Edit
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetLayout(
        Edit $subject,
        Edit $result,
        LayoutInterface $layout
    ): Edit {
        if (!$this->role->getIsAll()) {
            $subject->removeButton('save');
            $subject->removeButton('save_and_edit_button');
            $subject->removeButton('reset');
        }
        return $result;
    }
}
