<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Test\Unit\ViewModel;

use Magento\QuickCheckout\Setup\MetadataData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\StoreManagerInterface;
use Magento\QuickCheckout\Model\Config;
use Magento\QuickCheckout\ViewModel\Metadata;

/**
 * @see Metadata
 */
class MetadataTest extends TestCase
{
    private const PRODUCTION_ENABLED = 'Production_enabled';
    private const PRODUCTION_DISABLED = 'Production_disabled';
    private const SANDBOX_ENABLED = 'Sandbox_enabled';
    private const SANDBOX_DISABLED = 'Sandbox_disabled';

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var Session|MockObject
     */
    private $authSession;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var File|MockObject
     */
    private $driverFile;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var MetadataData|MockObject
     */
    private $metadataData;

    /**
     * @var ReflectionMethod
     */
    private $getMaxModeMethod;

    /**
     * @inheritDoc
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->authSession = $this->createMock(Session::class);
        $this->config = $this->createMock(Config::class);
        $this->driverFile = $this->createMock(File::class);
        $this->context = $this->createMock(Context::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->metadataData = $this->createMock(MetadataData::class);

        $this->metadata = new Metadata(
            $this->authSession,
            $this->config,
            $this->driverFile,
            $this->context,
            $this->storeManager,
            $this->metadataData
        );
        $this->getMaxModeMethod = new ReflectionMethod($this->metadata, 'getMaxMode');
        $this->getMaxModeMethod->setAccessible(true);
    }

    public function testGetMaxModeEmptyStrings(): void
    {
        $this->assertEmpty($this->getMaxModeMethod->invoke($this->metadata, '', ''));
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetMaxModeOneEmptyString(): void
    {
        $this->assertGetMaxModeMethod(self::SANDBOX_DISABLED, self::SANDBOX_DISABLED, '');
        $this->assertGetMaxModeMethod(self::PRODUCTION_ENABLED, '', self::PRODUCTION_ENABLED);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetMaxModeSameValueString(): void
    {
        $this->assertGetMaxModeMethod(self::SANDBOX_DISABLED, self::SANDBOX_DISABLED, self::SANDBOX_DISABLED);
        $this->assertGetMaxModeMethod(self::SANDBOX_ENABLED, self::SANDBOX_ENABLED, self::SANDBOX_ENABLED);
        $this->assertGetMaxModeMethod(self::PRODUCTION_DISABLED, self::PRODUCTION_DISABLED, self::PRODUCTION_DISABLED);
        $this->assertGetMaxModeMethod(self::PRODUCTION_ENABLED, self::PRODUCTION_ENABLED, self::PRODUCTION_ENABLED);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetMaxModeProductionEnabled(): void
    {
        $this->assertGetMaxModeMethod(self::PRODUCTION_ENABLED, self::SANDBOX_DISABLED, self::PRODUCTION_ENABLED);
        $this->assertGetMaxModeMethod(self::PRODUCTION_ENABLED, self::PRODUCTION_ENABLED, self::SANDBOX_ENABLED);
        $this->assertGetMaxModeMethod(self::PRODUCTION_ENABLED, self::PRODUCTION_DISABLED, self::PRODUCTION_ENABLED);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetMaxModeProductionDisabled(): void
    {
        $this->assertGetMaxModeMethod(self::PRODUCTION_DISABLED, self::SANDBOX_DISABLED, self::PRODUCTION_DISABLED);
        $this->assertGetMaxModeMethod(self::PRODUCTION_DISABLED, self::PRODUCTION_DISABLED, self::SANDBOX_ENABLED);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetMaxModeSandboxEnabled(): void
    {
        $this->assertGetMaxModeMethod(self::SANDBOX_ENABLED, self::SANDBOX_DISABLED, self::SANDBOX_ENABLED);
        $this->assertGetMaxModeMethod(self::SANDBOX_ENABLED, self::SANDBOX_ENABLED, self::SANDBOX_DISABLED);
    }

    /**
     * @param string $expected
     * @param string $rhs
     * @param string $lhs
     * @return void
     * @throws \ReflectionException
     */
    private function assertGetMaxModeMethod(string $expected, string $rhs, string $lhs): void
    {
        $this->assertEquals(
            $expected,
            $this->getMaxModeMethod->invoke($this->metadata, $rhs, $lhs)
        );
    }
}
