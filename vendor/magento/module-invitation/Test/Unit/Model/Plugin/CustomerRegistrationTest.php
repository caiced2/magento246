<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Test\Unit\Model\Plugin;

use Magento\Customer\Model\Registration;
use Magento\Framework\App\RequestInterface;
use Magento\Invitation\Helper\Data;
use Magento\Invitation\Model\Config;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\InvitationProvider;
use Magento\Invitation\Model\Plugin\CustomerRegistration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerRegistrationTest extends TestCase
{
    /**
     * @var CustomerRegistration
     */
    protected $_model;

    /**
     * @var Config|MockObject
     */
    protected $_invitationConfig;

    /**
     * @var Data|MockObject
     */
    protected $_invitationHelper;

    /**
     * @var Registration|MockObject
     */
    protected $subjectMock;

    /**
     * @var InvitationProvider|MockObject
     */
    private $invitationProviderMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    protected function setUp(): void
    {
        $this->_invitationConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_invitationHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subjectMock = $this->getMockBuilder(Registration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->invitationProviderMock = $this->getMockBuilder(InvitationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->_model = new CustomerRegistration(
            $this->_invitationConfig,
            $this->_invitationHelper,
            $this->invitationProviderMock,
            $this->requestMock
        );
    }

    /**
     * Check basic logic of afterIsAllowed method
     *
     * @dataProvider afterIsAllowedMethodDataProvider
     */
    public function testAfterIsAllowedMethod(
        $invocationResult,
        $isInvitationEnabled,
        $isInvitationRequired,
        $invitationId,
        $expected
    ) {
        $this->_invitationConfig->expects($this->any())
            ->method('isEnabled')
            ->willReturn($isInvitationEnabled);

        $this->_invitationConfig->expects($this->any())
            ->method('getInvitationRequired')
            ->willReturn($isInvitationRequired);

        $invitationMock = $this->getMockBuilder(Invitation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invitationMock->expects($this->any())
            ->method('getId')
            ->willReturn($invitationId);

        $this->invitationProviderMock->expects($this->any())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($invitationMock);

        $this->assertEquals($expected, $this->_model->afterIsAllowed($this->subjectMock, $invocationResult));
    }

    /**
     * Provides test data for testAfterIsAllowedMethod test
     *
     * @return array
     */
    public function afterIsAllowedMethodDataProvider()
    {
        return [
            [
                'invocation_result' => false,
                'invitation_enabled' => false,
                'invitation_required' => false,
                'invitation_id' => null,
                'expected' => false,
            ],
            [
                'invocation_result' => true,
                'invitation_enabled' => false,
                'invitation_required' => false,
                'invitation_id' => null,
                'expected' => true,
            ],
            [
                'invocation_result' => true,
                'invitation_enabled' => true,
                'invitation_required' => false,
                'invitation_id' => null,
                'expected' => true,
            ],
            [
                'invocation_result' => true,
                'invitation_enabled' => true,
                'invitation_required' => true,
                'invitation_id' => null,
                'expected' => false,
            ],
            [
                'invocation_result' => true,
                'invitation_enabled' => true,
                'invitation_required' => true,
                'invitation_id' => 1,
                'expected' => true,
            ],
        ];
    }

    /**
     * Check that if exception occurs then method returns FALSE
     */
    public function testAfterIsAllowedMethodWithException()
    {
        $invocationResult = true;
        $isInvitationEnabled = true;
        $isInvitationRequired = true;

        $this->_invitationConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn($isInvitationEnabled);

        $this->_invitationConfig->expects($this->once())
            ->method('getInvitationRequired')
            ->willReturn($isInvitationRequired);

        $this->invitationProviderMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willThrowException(new \Exception());

        $this->assertFalse($this->_model->afterIsAllowed($this->subjectMock, $invocationResult));
    }
}
