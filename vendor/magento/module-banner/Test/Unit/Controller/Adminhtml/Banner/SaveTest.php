<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Banner\Controller\Adminhtml\Banner\Save;
use Magento\Banner\Model\Banner;
use Magento\Banner\Model\Banner\Validator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var Save
     */
    protected $saveController;

    /**
     * @var Validator|MockObject
     */
    protected $bannerValidatorMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var Redirect|MockObject
     */
    protected $redirectMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->bannerValidatorMock = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepareSaveData'])
            ->getMock();

        $this->saveController = $this->objectManager->getObject(
            Save::class,
            [
                'context' => $this->prepareContext(),
                'bannerValidator' => $this->bannerValidatorMock
            ]
        );
    }

    protected function prepareContext()
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMockForAbstractClass();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['setFormData'])
            ->disableOriginalConstructor()
            ->getMock();

        $resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirectMock);

        $context = $this->objectManager->getObject(
            Context::class,
            [
                'request' => $this->requestMock,
                'resultFactory' => $resultFactoryMock,
                'objectManager' => $this->objectManagerMock,
                'messageManager' => $this->messageManagerMock,
                'session' => $this->sessionMock
            ]
        );

        return $context;
    }

    public function testExecuteNoPostData()
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('back', false)
            ->willReturn(false);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn(null);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/')
            ->willReturnSelf();

        $this->assertInstanceOf(
            Redirect::class,
            $this->saveController->execute()
        );
    }

    public function testExecuteBannerNoExist()
    {
        $bannerId = 10;
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['back', false, false],
                    ['banner_id', null, $bannerId],
                    ['id', null, $bannerId]
                ]
            );

        $bannerMock = $this->getBannerModel();
        $bannerMock->expects($this->once())
            ->method('setStoreId')
            ->with(0)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('load')
            ->with($bannerId)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn(['key', 'value']);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/')
            ->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('This dynamic block does not exist.'))
            ->willReturnSelf();

        $this->assertInstanceOf(
            Redirect::class,
            $this->saveController->execute()
        );
    }

    protected function getBannerModel()
    {
        $bannerMock = $this->getMockBuilder(Banner::class)
            ->disableOriginalConstructor()
            ->setMethods(['setStoreId', 'load', 'getId', 'save', 'addData', 'getStoreContents'])
            ->getMock();

        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->with(Banner::class)
            ->willReturn($bannerMock);

        return $bannerMock;
    }

    public function testExecuteWithLocalizedException()
    {
        $bannerId = 10;
        $storeId = 0;
        $postData = ['key', 'value'];
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['back', false, false],
                    ['banner_id', null, $bannerId],
                    ['id', null, $bannerId]
                ]
            );

        $bannerMock = $this->getBannerModel();
        $bannerMock->expects($this->once())
            ->method('setStoreId')
            ->with($storeId)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('load')
            ->with($bannerId)
            ->willReturnSelf();
        $bannerMock->expects($this->any())
            ->method('getId')
            ->willReturn($bannerId);
        $bannerMock->expects($this->once())
            ->method('save')
            ->willThrowException(new LocalizedException(__('Error')));

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/edit', ['id' => $bannerId, 'store' => $storeId])
            ->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Error'))
            ->willReturnSelf();

        $this->bannerValidatorMock->expects($this->once())
            ->method('prepareSaveData')
            ->with($postData)
            ->willReturn([]);

        $this->assertInstanceOf(
            Redirect::class,
            $this->saveController->execute()
        );
    }

    public function testExecuteWithException()
    {
        $bannerId = 10;
        $storeId = 0;
        $postData = ['key', 'value'];
        $exception = new \Exception('Error');
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['back', false, false],
                    ['banner_id', null, $bannerId],
                    ['id', null, $bannerId]
                ]
            );

        $bannerMock = $this->getBannerModel();
        $bannerMock->expects($this->once())
            ->method('setStoreId')
            ->with($storeId)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('load')
            ->with($bannerId)
            ->willReturnSelf();
        $bannerMock->expects($this->any())
            ->method('getId')
            ->willReturn($bannerId);
        $bannerMock->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/edit', ['id' => $bannerId, 'store' => $storeId])
            ->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('We cannot save the dynamic block.'))
            ->willReturnSelf();

        $this->bannerValidatorMock->expects($this->once())
            ->method('prepareSaveData')
            ->with($postData)
            ->willReturn([]);

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception)
            ->willReturnSelf();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($loggerMock);

        $this->assertInstanceOf(
            Redirect::class,
            $this->saveController->execute()
        );
    }

    public function testExecute()
    {
        $bannerId = 10;
        $postData = ['key', 'value'];
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['back', false, false],
                    ['banner_id', null, $bannerId],
                    ['id', null, $bannerId]
                ]
            );

        $bannerMock = $this->getBannerModel();
        $bannerMock->expects($this->any())
            ->method('getStoreContents')
            ->willReturn(null);
        $bannerMock->expects($this->once())
            ->method('setStoreId')
            ->with(0)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('load')
            ->with($bannerId)
            ->willReturnSelf();
        $bannerMock->expects($this->any())
            ->method('getId')
            ->willReturn($bannerId);
        $bannerMock->expects($this->once())
            ->method('addData')
            ->with($postData)
            ->willReturnSelf();
        $bannerMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn($postData);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*/')
            ->willReturnSelf();

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('You saved the dynamic block.'))
            ->willReturnSelf();

        $this->bannerValidatorMock->expects($this->once())
            ->method('prepareSaveData')
            ->with($postData)
            ->willReturn($postData);

        $this->sessionMock->expects($this->any())
            ->method('setFormData')
            ->willReturnMap(
                [
                    [$postData, null],
                    [false, null]
                ]
            );

        $this->assertInstanceOf(
            Redirect::class,
            $this->saveController->execute()
        );
    }
}
