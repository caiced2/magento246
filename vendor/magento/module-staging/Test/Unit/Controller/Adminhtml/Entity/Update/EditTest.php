<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Controller\Adminhtml\Entity\Update;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Controller\Adminhtml\Update\Edit;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var MockObject
     */
    private $contextMock;

    /**
     * @var MockObject
     */
    private $messageManagerMock;

    /**
     * @var MockObject
     */
    private $resultFactoryMock;

    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $resultRedirectFactoryMock;

    /**
     * @var Edit
     */
    private $edit;

    protected function setUp(): void
    {
        $this->updateRepositoryMock = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactoryMock = $this->getMockBuilder(
            RedirectFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $objectManager = new ObjectManager($this);
        $this->edit = $objectManager->getObject(
            Edit::class,
            [
                'context' => $this->contextMock,
                'updateRepository' => $this->updateRepositoryMock
            ]
        );
    }

    /**
     * @dataProvider emptyIdProvider
     * @param mixed $emptyId
     */
    public function testExecuteEmptyId($emptyId)
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($emptyId);
        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage');

        $resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('staging/update')
            ->willReturnSelf();

        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultRedirectMock);

        $this->assertEquals($resultRedirectMock, $this->edit->execute());
    }

    public static function emptyIdProvider()
    {
        return [
            [''],
            ['test'],
            [0]
        ];
    }

    public function testExecuteNoEntity()
    {
        $notExistedId = 123;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($notExistedId);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage');

        $resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('staging/update')
            ->willReturnSelf();
        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultRedirectMock);

        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->with($notExistedId)
            ->willThrowException(new NoSuchEntityException(__('Test')));

        $this->assertEquals($resultRedirectMock, $this->edit->execute());
    }

    public function testExecute()
    {
        $existedId = 123;
        $updateName = '1st April';

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($existedId);

        $updateMock = $this->getMockBuilder(UpdateInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $updateMock->expects($this->once())
            ->method('getName')
            ->willReturn($updateName);

        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->with($existedId)
            ->willReturn($updateMock);

        $resultPage = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'getTitle', 'prepend'])
            ->getMockForAbstractClass();
        $resultPage->expects($this->once())
            ->method('getConfig')
            ->willReturnSelf();
        $resultPage->expects($this->once())
            ->method('getTitle')
            ->willReturnSelf();
        $resultPage->expects($this->once())
            ->method('prepend')
            ->with($updateName)
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultPage);

        $this->assertEquals($resultPage, $this->edit->execute());
    }
}
