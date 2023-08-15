<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Block\Adminhtml\Permissions\Tab\Rolesedit;

use Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws;
use Magento\AdminGws\Model\Role;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws testing
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GwsTest extends TestCase
{
    /**
     * @var  Gws
     */
    protected $block;

    /**
     * @var  Context
     */
    protected $context;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var Session
     */
    protected $backendSession;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var Role
     */
    protected $adminGwsRole;

    /**
     * @var  ObjectManager
     */
    protected $objectManager;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->context = $this->createPartialMock(
            Context::class,
            ['getBackendSession']
        );
        $this->jsonEncoder = $this->getMockForAbstractClass(EncoderInterface::class);
        $this->adminGwsRole = $this->getMockBuilder(Role::class)
            ->addMethods(['getGwsWebsites', 'getGwsStoreGroups'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->coreRegistry =  $this->createPartialMock(Registry::class, ['registry']);
        $this->backendSession =  $this->createPartialMock(Session::class, ['getData']);

        $this->context->expects($this->any())->method('getBackendSession')->willReturn($this->backendSession);
    }

    /**
     * @return void
     */
    public function testGetGwsWebsitesFromSession(): void
    {
        $this->backendSession
            ->method('getData')
            ->withConsecutive([], [Gws::SCOPE_WEBSITE_FORM_DATA_SESSION_KEY, true])
            ->willReturnOnConsecutiveCalls(null, [0 => '1']);

        $this->block = $this->objectManager->getObject(
            Gws::class,
            [
                'context' => $this->context,
                'jsonEncoder' => $this->jsonEncoder,
                'adminGwsRole' => $this->adminGwsRole,
                'coreRegistry' => $this->coreRegistry
            ]
        );

        $this->assertEquals([0 => '1'], $this->block->getGwsWebsites());
    }

    /**
     * @return void
     */
    public function testGetGwsStoreGroupsFromSession(): void
    {
        $this->backendSession
            ->method('getData')
            ->withConsecutive([], [], [Gws::SCOPE_STORE_FORM_DATA_SESSION_KEY, true])
            ->willReturnOnConsecutiveCalls(null, null, [0 => '1']);

        $this->block = $this->objectManager->getObject(
            Gws::class,
            [
                'context' => $this->context,
                'jsonEncoder' => $this->jsonEncoder,
                'adminGwsRole' => $this->adminGwsRole,
                'coreRegistry' => $this->coreRegistry
            ]
        );
        $this->assertEquals([0 => '1'], $this->block->getGwsStoreGroups());
    }

    /**
     * @return void
     */
    public function testGetGwsWebsitesFromRegistry(): void
    {
        $this->backendSession
            ->method('getData')
            ->withConsecutive([], [Gws::SCOPE_WEBSITE_FORM_DATA_SESSION_KEY, true])
            ->willReturnOnConsecutiveCalls(null, null);

        $this->block = $this->objectManager->getObject(
            Gws::class,
            [
                'context' => $this->context,
                'jsonEncoder' => $this->jsonEncoder,
                'adminGwsRole' => $this->adminGwsRole,
                'coreRegistry' => $this->coreRegistry
            ]
        );

        $this->coreRegistry->expects($this->once())->method('registry')->willReturn($this->adminGwsRole);
        $this->adminGwsRole->expects($this->once())->method('getGwsWebsites')->willReturn([0 => '1']);

        $this->assertEquals([0 => '1'], $this->block->getGwsWebsites());
    }

    /**
     * @return void
     */
    public function testGetGwsStoreGroupsFromRegistry(): void
    {
        $this->backendSession
            ->method('getData')
            ->withConsecutive([], [], [Gws::SCOPE_STORE_FORM_DATA_SESSION_KEY, true])
            ->willReturnOnConsecutiveCalls(null, null, null);

        $this->block = $this->objectManager->getObject(
            Gws::class,
            [
                'context' => $this->context,
                'jsonEncoder' => $this->jsonEncoder,
                'adminGwsRole' => $this->adminGwsRole,
                'coreRegistry' => $this->coreRegistry
            ]
        );

        $this->coreRegistry->expects($this->once())->method('registry')->willReturn($this->adminGwsRole);
        $this->adminGwsRole->expects($this->once())->method('getGwsStoreGroups')->willReturn([0 => '1']);

        $this->assertEquals([0 => '1'], $this->block->getGwsStoreGroups());
    }
}
