<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Controller\Adminhtml\Page\Save;

use Magento\Cms\Controller\Adminhtml\Page\Save;
use Magento\CmsStaging\Controller\Adminhtml\Page\Save\Plugin;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    private $controller;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDate;

    protected function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->localeDate = $this->getMockForAbstractClass(TimezoneInterface::class);

        $this->controller = new Plugin(
            $this->logger,
            $this->localeDate
        );
    }

    /**
     * @dataProvider dataProviderBeforeExecute
     * @param mixed $customTheme
     * @param bool $hasCustomeTheme
     */
    public function testBeforeExecute(
        $customTheme,
        $hasCustomeTheme
    ) {
        $date = '2000-01-01';
        $this->localeDate->method('formatDate')
            ->willReturn($date);

        $pageSaveMock = $this->createMock(Save::class);
        $requestMock = $this->createMock(Http::class);

        $requestMock->expects($this->once())
            ->method('getPostValue')
            ->with('custom_theme')
            ->willReturn($customTheme);

        if ($hasCustomeTheme) {
            $requestMock->expects($this->once())
                ->method('setPostValue')
                ->with('custom_theme_from', $date)
                ->willReturnSelf();
        } else {
            $requestMock->expects($this->once())
                ->method('setPostValue')
                ->with('custom_theme_from', null)
                ->willReturnSelf();
        }

        $pageSaveMock->expects($this->exactly(2))
            ->method('getRequest')
            ->willReturn($requestMock);

        $this->controller->beforeExecute($pageSaveMock);
    }

    /**
     * @return array
     */
    public function dataProviderBeforeExecute()
    {
        return [
            [1, true],
            ['1', true],
            [0, false],
            ['test', false],
            [null, false],
            ['', false],
        ];
    }

    public function testBeforeExecuteException()
    {
        $pageSaveMock = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exception = new \Exception('Error');

        $pageSaveMock->expects($this->once())
            ->method('getRequest')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exception)
            ->willReturnSelf();

        $this->controller->beforeExecute($pageSaveMock);
    }
}
