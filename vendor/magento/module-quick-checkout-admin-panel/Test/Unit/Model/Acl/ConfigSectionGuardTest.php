<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Test\Unit\Model\Acl;

use Magento\Authorization\Model\Acl\AclRetriever;
use Magento\Authorization\Model\Role;
use Magento\QuickCheckoutAdminPanel\Model\Acl\ConfigSectionGuard;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigSectionGuardTest extends TestCase
{
    private const TEST_RESOURCE_1 = 'Magento_Backend::testResource1';
    private const TEST_RESOURCE_2 = 'Magento_Backend::testResource2';

    /**
     * @var AclRetriever|MockObject
     */
    private AclRetriever $aclRetriever;

    /**
     * @var string[]
     */
    private array $requiredResources = [self::TEST_RESOURCE_1, 'Magento_Backend::testResource2'];

    /**
     * @var ConfigSectionGuard
     */
    private ConfigSectionGuard $configSectionGuard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aclRetriever = $this->createMock(AclRetriever::class);
        $this->configSectionGuard = new ConfigSectionGuard($this->aclRetriever, $this->requiredResources);
    }

    public function testUserWithoutPermissions()
    {
        $user = $this->givenTheUser();
        $this->givenUserHasPermissions([]);
        $this->assertFalse($this->configSectionGuard->isAllowed($user));
    }

    public function testUserAdmin()
    {
        $user = $this->givenTheUser();
        $this->givenUserHasPermissions([ConfigSectionGuard::ALL_ACCESS_RESOURCE]);
        $this->assertTrue($this->configSectionGuard->isAllowed($user));
    }

    public function testUserWithMissingPermissions()
    {
        $user = $this->givenTheUser();
        $this->givenUserHasPermissions([]);
        $this->assertFalse($this->configSectionGuard->isAllowed($user));
    }

    public function testUserWithAllPermissions()
    {
        $user = $this->givenTheUser();
        $this->givenUserHasPermissions([self::TEST_RESOURCE_1, self::TEST_RESOURCE_2]);
        $this->assertTrue($this->configSectionGuard->isAllowed($user));
    }

    /**
     * @return User|MockObject
     */
    private function givenTheUser(): MockObject
    {
        $role = $this->createMock(Role::class);
        $role->method('getId')->willReturn(1);
        $user = $this->createMock(User::class);
        $user->method('getRole')->willReturn($role);
        return $user;
    }

    /**
     * @param array $resources
     * @return void
     */
    public function givenUserHasPermissions(array $resources): void
    {
        $this->aclRetriever->expects(self::once())
            ->method('getAllowedResourcesByRole')
            ->willReturn($resources);
    }
}
