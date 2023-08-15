<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Controller\Adminhtml\Page\Update\Save;

use Magento\CmsStaging\Controller\Adminhtml\Page\Update\Save;
use Magento\CmsStaging\Controller\Adminhtml\Page\Update\Save\Plugin;
use Magento\Framework\App\Request\Http;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    protected $controller;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    protected $updateRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    protected function setUp(): void
    {
        $this->updateRepository = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->controller = new Plugin(
            $this->updateRepository,
            $this->logger
        );
    }

    /**
     * @dataProvider dataProviderBeforeExecute
     * @param mixed $customTheme
     * @param bool $hasCustomeTheme
     * @param string $mode
     */
    public function testBeforeExecute(
        $customTheme,
        $hasCustomeTheme,
        $mode
    ) {
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));

        $staging = [
            'select_id' => 1,
            'start_time' => $currentDate->format('Y-m-d H:i:s'),
            'mode' => $mode,
        ];

        $pageSaveMock = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturnMap([
                ['custom_theme', null, $customTheme],
                ['staging', null, $staging],
            ]);

        $pageSaveMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($requestMock);

        if ($hasCustomeTheme) {
            if ($mode == 'assign' || $mode == 'save') {
                $requestMock->expects($this->once())
                    ->method('setPostValue')
                    ->with('custom_theme_from', $currentDate->format('m/d/Y'))
                    ->willReturnSelf();
            }
        } else {
            $requestMock->expects($this->once())
                ->method('setPostValue')
                ->with('custom_theme_from', null)
                ->willReturnSelf();
        }

        if ($mode == 'assign') {
            $updateMock = $this->getMockBuilder(UpdateInterface::class)
                ->getMockForAbstractClass();

            $updateMock->expects($this->once())
                ->method('getStartTime')
                ->willReturn($currentDate->format('Y-m-d H:i:s'));

            $this->updateRepository->expects($this->once())
                ->method('get')
                ->with()
                ->willReturn($updateMock);
        }

        $result = $this->controller->beforeExecute($pageSaveMock);
        $this->assertNull($result);
    }

    /**
     * @return array
     */
    public function dataProviderBeforeExecute()
    {
        return [
            [1, true, 'assign'],
            [1, true, 'save'],
            [1, true, ''],
            ['1', true, ''],
            [0, false, ''],
            ['test', false, ''],
            [null, false, ''],
            ['', false, ''],
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

        $result = $this->controller->beforeExecute($pageSaveMock);
        $this->assertNull($result);
    }
}
