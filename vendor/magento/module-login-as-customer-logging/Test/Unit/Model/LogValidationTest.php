<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Test\Unit\Model;

use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdmin;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\Logging\Model\Config;
use Magento\LoginAsCustomerLogging\Model\LogValidation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogValidationTest extends TestCase
{
    /**
     * @var LogValidation
     */
    private LogValidation $logValidator;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var GetLoggedAsCustomerAdminIdInterface|MockObject
     */
    private $getLoggedAsCustomerAdminIdInterface;

    protected function setUp(): void
    {
        $this->getLoggedAsCustomerAdminIdInterface = $this->createMock(GetLoggedAsCustomerAdminIdInterface::class);
        $this->config = $this->createMock(Config::class);

        $this->logValidator = new LogValidation($this->getLoggedAsCustomerAdminIdInterface, $this->config);
    }

    /**
     * @dataProvider dataProvider
     * @param $config
     * @param $loggedAsCustomer
     * @param $expected
     */
    public function testShouldBeLogged($config, $loggedAsCustomer, $expected, $loggedAsCustomerIsCalled)
    {
        if ($loggedAsCustomerIsCalled) {
            $this->getLoggedAsCustomerAdminIdInterface = $this->getLoggedAsCustomerAdminIdInterface
                ->expects($this->once());
        } else {
            $this->getLoggedAsCustomerAdminIdInterface = $this->getLoggedAsCustomerAdminIdInterface
                ->expects($this->never());
        }
        $this->getLoggedAsCustomerAdminIdInterface
            ->method('execute')
            ->willReturn($loggedAsCustomer);
        $this->config->expects($this->once())
            ->method('isEventGroupLogged')
            ->willReturn($config);
        $this->assertEquals($expected, $this->logValidator->shouldBeLogged());
    }

    public function dataProvider()
    {
        return [
            [
                'config' => true,
                'loggedAsCustomer' => 1,
                'expected' => true,
                'loggedAsCustomerIsCalled' => true
            ],
            [
                'config' => true,
                'loggedAsCustomer' => 0,
                'expected' => false,
                'loggedAsCustomerIsCalled' => true
            ],
            [
                'config' => false,
                'loggedAsCustomer' => 1,
                'expected' => false,
                'loggedAsCustomerIsCalled' => false
            ],
            [
                'config' => false,
                'loggedAsCustomer' => 0,
                'expected' => false,
                'loggedAsCustomerIsCalled' => false
            ],
        ];
    }
}
