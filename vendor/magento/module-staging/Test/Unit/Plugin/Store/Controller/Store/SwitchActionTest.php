<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Plugin\Store\Controller\Store;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Plugin\Store\Controller\Store\SwitchAction;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for plugin for store switch action.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SwitchActionTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var SwitchAction
     */
    private $subject;

    /**
     * @var string
     */
    private $redirectUrl = 'http://magento.dev/sales/guest/form/?___store=';

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ResponseInterface|MockObject
     */
    private $responseMock;

    /**
     * @var RedirectInterface|MockObject
     */
    private $redirectMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Store\Controller\Store\SwitchAction|MockObject
     */
    private $switchActionMock;

    /**
     * @var array
     */
    private $storeCodes = [
        'old' => 'test_store_old',
        'new' => 'test_store_new'
    ];

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var StoreRepositoryInterface|MockObject
     */
    private $storeRepositoryMock;

    protected function setUp(): void
    {
        $storeMock = $this->getMockForAbstractClass(
            StoreInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn($this->storeCodes['new']);

        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getPost']
        );
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with(StoreManagerInterface::PARAM_NAME)
            ->willReturn($this->storeCodes['new']);

        $this->responseMock = $this->getMockForAbstractClass(
            ResponseInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setRedirect']
        );

        $this->redirectMock = $this->getMockForAbstractClass(
            RedirectInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->redirectMock->expects($this->any())
            ->method('getRedirectUrl')
            ->willReturn($this->redirectUrl . $this->storeCodes['old']);

        $this->objectManager = new ObjectManager($this);

        $this->switchActionMock = $this->createMock(\Magento\Store\Controller\Store\SwitchAction::class);
        $this->switchActionMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->storeRepositoryMock = $this->getMockForAbstractClass(
            StoreRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->storeRepositoryMock->expects($this->any())
            ->method('getActiveStoreByCode')
            ->with($this->storeCodes['new'])
            ->willReturn($storeMock);

        $this->subject = $this->objectManager->getObject(
            SwitchAction::class,
            [
                'request' => $this->requestMock,
                'versionManager' => $this->versionManagerMock,
                'redirect' => $this->redirectMock,
                'storeRepository' => $this->storeRepositoryMock
            ]
        );
    }

    /**
     * @param bool $isPreview
     * @param bool $isException
     *
     * @dataProvider dataProviderAroundExecute
     */
    public function testAroundExecute($isPreview, $isException)
    {
        $closureMock = function () {
            return;
        };

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        // Assertions.
        if (!$isPreview) {
            $this->responseMock->expects($this->never())
                ->method('setRedirect');
        }

        if ($isPreview && $isException) {
            $this->storeRepositoryMock->expects($this->any())
                ->method('getActiveStoreByCode')
                ->willThrowException(
                    new LocalizedException(
                        new Phrase('Test Exception')
                    )
                );

            $this->responseMock->expects($this->never())
                ->method('setRedirect');
        }

        if ($isPreview && !$isException) {
            $this->responseMock->expects($this->once())
                ->method('setRedirect')
                ->with($this->redirectUrl . $this->storeCodes['new']);
        }

        $this->subject->aroundExecute($this->switchActionMock, $closureMock);
    }

    /**
     * @return array
     */
    public function dataProviderAroundExecute()
    {
        return [
            [false, true],
            [true, true],
            [true, false]
        ];
    }
}
