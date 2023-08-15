<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SalesArchive\Model\Config;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfig;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function testIsArchiveActive()
    {
        $isActive = false;
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                Config::XML_PATH_ARCHIVE_ACTIVE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn($isActive);
        $this->assertEquals($isActive, $this->config->isArchiveActive());
    }

    public function testGetArchiveAge()
    {
        $age = 12;

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_ARCHIVE_AGE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn($age);
        $this->assertEquals($age, $this->config->getArchiveAge());
    }

    public function testGetArchiveOrderStatuses()
    {
        $statuses = 'archived,closed';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_ARCHIVE_ORDER_STATUSES,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn($statuses);
        $statuses = explode(',', $statuses);
        $this->assertEquals($statuses, $this->config->getArchiveOrderStatuses());
    }

    public function testGetArchiveOrderStatusesEmpty()
    {
        $empty = [];
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_ARCHIVE_ORDER_STATUSES,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn($empty);
        $this->assertEquals($empty, $this->config->getArchiveOrderStatuses());
    }
}
