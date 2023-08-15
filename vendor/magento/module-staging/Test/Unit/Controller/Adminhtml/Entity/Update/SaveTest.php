<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Controller\Adminhtml\Entity\Update;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Controller\Adminhtml\Update\Save;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\UpdateFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /** @var MockObject */
    private $contextMock;

    /** @var MockObject */
    private $updateRepositoryMock;

    /** @var MockObject */
    private $updateFactoryMock;

    /** @var MockObject */
    private $compaignValidatorMock;

    /** @var Save */
    private $save;

    /** @var MockObject */
    private $messageManagerMock;

    /** @var MockObject */
    private $resultFactoryMock;

    /** @var MockObject */
    private $requestMock;

    /** @var MockObject */
    private $resultRedirectFactoryMock;

    /** @var MockObject */
    private $redirectMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepositoryMock = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateFactoryMock = $this->getMockBuilder(UpdateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->compaignValidatorMock = $this->getMockBuilder(
            CampaignValidator::class
        )
            ->disableOriginalConstructor()
            ->getMock();
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
        $this->save = $objectManager->getObject(
            Save::class,
            [
                'context' => $this->contextMock,
                'updateRepository' => $this->updateRepositoryMock,
                'campaignValidator' => $this->compaignValidatorMock,
            ]
        );
    }

    public function testExecute()
    {
        $generalData = [
            'id' => 123,
            'end_time' => '2030-01-31 16:22:09',
            'start_time' => '2029-12-12 16:22:09',
        ];

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('general')
            ->willReturn($generalData);
        $updateMock = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->expects($this->exactly(1))
            ->method('getStartTime')
            ->willReturn('2029-12-12 16:22:09');
        $updateMock->expects($this->once())
            ->method('setData')
            ->willReturn(true);
        $this->compaignValidatorMock->expects($this->once())
            ->method('canBeUpdated')
            ->willReturn(true);
        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($updateMock);
        $this->updateRepositoryMock->expects($this->once())
            ->method('save')
            ->with($updateMock)
            ->willReturn(true);
        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->willReturnSelf();

        $this->assertEquals($this->redirectMock, $this->save->execute());
    }

    public function testExecuteWithPastStartDate()
    {
        $generalData = [
            'id' => 124,
            'end_time' => '2017-01-31 16:22:09',
            'start_time' => '2016-12-12 16:22:09',
        ];

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('general')
            ->willReturn($generalData);
        $updateMock = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();

        $updateMock->expects($this->exactly(1))
            ->method('getStartTime')
            ->willReturn('2016-12-12 16:22:09');
        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($updateMock);
        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->redirectMock);

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->willReturnSelf();

        $this->assertEquals($this->redirectMock, $this->save->execute());
    }
}
