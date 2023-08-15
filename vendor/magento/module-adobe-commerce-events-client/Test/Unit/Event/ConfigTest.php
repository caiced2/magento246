<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for config class
 */
class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfigMock);
    }

    public function testGetMerchantId()
    {
        $merchantId = 'demo';
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('adobe_io_events/eventing/merchant_id')
            ->willReturn($merchantId);

        self::assertEquals(
            $merchantId,
            $this->config->getMerchantId()
        );
    }

    public function testGetEnvironmentId()
    {
        $environmentId = 'demo';
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('adobe_io_events/eventing/env_id')
            ->willReturn($environmentId);

        self::assertEquals(
            $environmentId,
            $this->config->getEnvironmentId()
        );
    }
}
