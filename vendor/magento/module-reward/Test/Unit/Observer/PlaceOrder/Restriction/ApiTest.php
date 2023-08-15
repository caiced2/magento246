<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer\PlaceOrder\Restriction;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Reward\Observer\PlaceOrder\Restriction\Api;
use Magento\Reward\Observer\PlaceOrder\Restriction\Backend;
use Magento\Reward\Observer\PlaceOrder\Restriction\Frontend;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    /**
     * @var Api
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $userContextMock;

    /**
     * @var MockObject
     */
    protected $frontendMock;

    /**
     * @var MockObject
     */
    protected $backendMock;

    protected function setUp(): void
    {
        $this->userContextMock = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->frontendMock = $this->createMock(Frontend::class);
        $this->backendMock = $this->createMock(Backend::class);

        $this->_model = new Api(
            $this->frontendMock,
            $this->backendMock,
            $this->userContextMock
        );
    }

    /**
     * @param int $userType
     *
     * @dataProvider backendUserDataProvider
     */
    public function testIsAllowedWithBackendUser($userType)
    {
        $this->userContextMock->expects($this->once())->method('getUserType')->willReturn($userType);
        $this->backendMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->frontendMock->expects($this->never())->method('isAllowed');
        $this->assertTrue($this->_model->isAllowed());
    }

    public function backendUserDataProvider()
    {
        return [
            'admin' => [UserContextInterface::USER_TYPE_ADMIN],
            'integration' => [UserContextInterface::USER_TYPE_INTEGRATION]
        ];
    }

    public function frontendUserDataProvider()
    {
        return [
            'customer' => [UserContextInterface::USER_TYPE_CUSTOMER],
            'guest' => [UserContextInterface::USER_TYPE_GUEST]
        ];
    }

    /**
     * @param int $userType
     *
     * @dataProvider frontendUserDataProvider
     */
    public function testIsAllowedWithFrontendUser($userType)
    {
        $this->userContextMock->expects($this->once())->method('getUserType')->willReturn($userType);
        $this->backendMock->expects($this->never())->method('isAllowed');
        $this->frontendMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->assertTrue($this->_model->isAllowed());
    }
}
