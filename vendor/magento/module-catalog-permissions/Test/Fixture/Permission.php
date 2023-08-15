<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Fixture;

use Magento\Catalog\Model\Category;
use Magento\CatalogPermissions\Model\Permission as PermissionModel;
use Magento\CatalogPermissions\Model\PermissionFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission as PermissionResource;
use Magento\Framework\DataObject;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class Permission implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'category_id' => Category::TREE_ROOT_ID,
        'website_id' => null,
        'customer_group_id' => null,
        'grant_catalog_category_view' => PermissionModel::PERMISSION_PARENT,
        'grant_catalog_product_price' => PermissionModel::PERMISSION_PARENT,
        'grant_checkout_items' => PermissionModel::PERMISSION_PARENT,
    ];

    /** @var PermissionFactory */
    private PermissionFactory $permissionFactory;

    /** @var PermissionResource */
    private PermissionResource $permissionResource;

    /**
     * @param PermissionFactory $permissionFactory
     * @param PermissionResource $permissionResource
     */
    public function __construct(
        PermissionFactory $permissionFactory,
        PermissionResource $permissionResource
    ) {
        $this->permissionFactory = $permissionFactory;
        $this->permissionResource = $permissionResource;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $permission = $this->permissionFactory->create();
        $permission->setData(array_merge(self::DEFAULT_DATA, $data));
        $this->permissionResource->save($permission);

        return $permission;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $permission): void
    {
        $this->permissionResource->delete($permission);
    }
}
