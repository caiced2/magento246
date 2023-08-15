<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Plugin;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Plugin\UpdateCachePlugin;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateCachePluginTest extends TestCase
{
    /**
     * @var ConfigInterface|MockObject
     */
    private $permissionsConfigMock;

    /**
     * @var Session|MockObject
     */
    private $customerSession;

    /**
     * @var UpdateCachePlugin
     */
    private $plugin;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->permissionsConfigMock = $this->createMock(ConfigInterface::class);
        $this->customerSession = $this->createMock(Session::class);

        $this->plugin = new UpdateCachePlugin(
            $this->customerSession,
            $this->permissionsConfigMock
        );
    }

    /**
     * @param bool $isEnabled
     * @param array $data
     * @param array $expected
     * @return void
     * @dataProvider afterGetDataDataProvider
     */
    public function testAfterGetData($isEnabled, $data, $expected)
    {
        $customerGroupId = 3;
        $this->permissionsConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn($isEnabled);
        $this->customerSession->method('getCustomerGroupId')
            ->willReturn($customerGroupId);

        /** @var HttpContext|MockObject $httpContext */
        $httpContext = $this->createMock(HttpContext::class);

        $data = $this->plugin->afterGetData($httpContext, $data);
        $this->assertEquals($expected, $data);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function afterGetDataDataProvider()
    {
        return [
            [
                true,
                ['string' => 'abc', 'int' => 42, 'bool' => true],
                ['string' => 'abc', 'int' => 42, 'bool' => true, 'customer_group' => 3],
            ],
            [
                false,
                ['string' => 'abc', 'int' => 42, 'bool' => true],
                ['string' => 'abc', 'int' => 42, 'bool' => true],
            ]
        ];
    }
}
