<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGwsConfigurableProduct\Test\Unit\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\AdminGwsConfigurableProduct\Plugin\PermissionsDataChecker;
use Magento\ConfigurableProduct\Block\DataProviders\PermissionsData;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test that permissions are set correctly
 */
class PermissionsDataCheckerTest extends TestCase
{
    /**
     * Test that permissions are set correctly
     *
     * @param bool $isAll
     * @param bool $subjectResult
     * @param bool $expected
     * @dataProvider provideAfterIsAllowedToManageAttributes
     */
    public function testAfterIsAllowedToManageAttributes(bool $isAll, bool $subjectResult, bool $expected)
    {
        $objectManager = new ObjectManager($this);
        $role = $this->createMock(Role::class);
        $role->method('getIsAll')->willReturn($isAll);
        $model = $objectManager->getObject(
            PermissionsDataChecker::class,
            [
                'role' => $role
            ]
        );
        $permissionsDataProvider = $this->createMock(PermissionsData::class);
        $this->assertEquals(
            $expected,
            $model->afterIsAllowedToManageAttributes($permissionsDataProvider, $subjectResult)
        );
    }

    /**
     * @return array
     */
    public function provideAfterIsAllowedToManageAttributes(): array
    {
        return [
            [
                true,
                true,
                true,
            ],
            [
                true,
                false,
                false,
            ],
            [
                false,
                false,
                false,
            ],
            [
                false,
                true,
                false,
            ],
        ];
    }
}
