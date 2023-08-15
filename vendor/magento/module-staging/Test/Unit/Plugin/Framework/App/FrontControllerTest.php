<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Plugin\Framework\App;

use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Auth\StorageInterface;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\Preview\RequestSigner;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Plugin\Framework\App\FrontController;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for front controller interface plugin.
 */
class FrontControllerTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var FrontController
     */
    private $subject;

    /**
     * @var Auth|MockObject
     */
    private $authMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var RequestSigner|MockObject
     */
    private $requestSigner;

    protected function setUp(): void
    {
        $this->authMock = $this->createMock(Auth::class);
        $this->requestSigner = $this->createMock(RequestSigner::class);

        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);

        $this->objectManager = new ObjectManager($this);

        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->subject = $this->objectManager->getObject(
            FrontController::class,
            [
                'auth' => $this->authMock,
                'versionManager' => $this->versionManagerMock,
                'requestSigner' => $this->requestSigner
            ]
        );
    }

    /**
     * @param bool $isPreview
     * @param $requestIsValid
     * @param $shouldForward
     * @dataProvider dataProviderBeforeDispatch
     */
    public function testBeforeDispatch($isPreview, $requestIsValid, $shouldForward)
    {
        $frontControllerMock = $this->getMockForAbstractClass(FrontControllerInterface::class);

        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        $this->requestSigner->method('validateUrl')
            ->willReturn($requestIsValid);

        $this->requestMock->method('getRequestString')
            ->willReturn('foo');

        if ($shouldForward) {
            $this->requestMock->expects($this->once())
                ->method('setActionName')
                ->with('noroute');
        } else {
            $this->requestMock->expects($this->never())
                ->method('setActionName');
        }

        $this->subject->beforeDispatch($frontControllerMock, $this->requestMock);
    }

    /**
     * @return array
     */
    public function dataProviderBeforeDispatch()
    {
        return [
            [false, false, false],
            [true, false, true],
            [true, true, false],
        ];
    }

    /**
     * @param bool $isUserExists
     *
     * @return \Magento\Backend\Model\Auth\Credential\StorageInterface|MockObject|null
     */
    public function getUserMock($isUserExists)
    {
        if ($isUserExists) {
            return $this->createMock(\Magento\Backend\Model\Auth\Credential\StorageInterface::class);
        }

        return null;
    }
}
