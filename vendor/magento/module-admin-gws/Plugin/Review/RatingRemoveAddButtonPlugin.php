<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Review;

use Magento\AdminGws\Model\Role;
use Magento\Review\Block\Adminhtml\Rating;
use Magento\Framework\View\LayoutInterface;

/**
 * Product attribute set grid block plugin
 */
class RatingRemoveAddButtonPlugin
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
     * @param Rating $subject
     * @param Rating $result
     * @param LayoutInterface $layout
     * @return Rating
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetLayout(
        Rating $subject,
        Rating $result,
        LayoutInterface $layout
    ): Rating {
        if (!$this->role->getIsAll()) {
            $subject->removeButton('add');
        }
        return $result;
    }
}
