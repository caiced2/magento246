<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\AdminGws\Model\Role;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Categories;
use Magento\Framework\Stdlib\ArrayManager;

class CategoriesRoleRestrictions
{
    /**
     * @var ArrayManager
     * @since 101.0.0
     */
    private $arrayManager;

    /**
     * @var Role
     */
    private $role;

    /**
     * @param ArrayManager $arrayManager
     * @param Role $role
     */
    public function __construct(
        ArrayManager $arrayManager,
        Role $role
    ) {
        $this->arrayManager = $arrayManager;
        $this->role = $role;
    }

    /**
     * Restrict adding new categories for restricted website / store users
     *
     * @param Categories $subject
     * @param array $meta
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterModifyMeta(Categories $subject, array $meta)
    {
        $fieldCode = 'create_category_button';
        $containerPath = $this->arrayManager->findPath($fieldCode, $meta);

        if (!$this->role->getIsAll()) {
            $meta = $this->arrayManager->remove($containerPath, $meta);
        }
        return $meta;
    }
}
