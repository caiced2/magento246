<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer\PlaceOrder\Restriction;

use Magento\Framework\AuthorizationInterface;
use Magento\Reward\Helper\Data;
use Magento\Reward\Observer\PlaceOrder\Restriction\Backend;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackendTest extends TestCase
{
    /**
     * @var Backend
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_helper;

    /**
     * @var MockObject
     */
    protected $_authorizationMock;

    protected function setUp(): void
    {
        $this->_helper = $this->createMock(Data::class);
        $this->_authorizationMock = $this->getMockForAbstractClass(AuthorizationInterface::class);
        $this->_model = new Backend(
            $this->_helper,
            $this->_authorizationMock
        );
    }

    /**
     * @dataProvider isAllowedDataProvider
     * @param $expectedResult
     * @param $isEnabled
     * @param $isAllowed
     */
    public function testIsAllowed($expectedResult, $isEnabled, $isAllowed)
    {
        $this->_helper->expects($this->once())->method('isEnabledOnFront')->willReturn($isEnabled);
        $this->_authorizationMock->expects($this->any())->method('isAllowed')->willReturn($isAllowed);
        $this->assertEquals($expectedResult, $this->_model->isAllowed());
    }

    public function isAllowedDataProvider()
    {
        return [[true, true, true], [false, true, false], [false, false, false]];
    }
}
