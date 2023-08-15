<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Test\Unit\Model\Plugin;

use Magento\Customer\Model\Registration;
use Magento\Store\Model\Store;
use Magento\WebsiteRestriction\Model\ConfigInterface;
use Magento\WebsiteRestriction\Model\Mode;
use Magento\WebsiteRestriction\Model\Plugin\CustomerRegistration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerRegistrationTest extends TestCase
{
    /**
     * @var CustomerRegistration
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $restrictionConfig;

    /**
     * @var MockObject
     */
    protected $subjectMock;

    protected function setUp(): void
    {
        $this->restrictionConfig = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->subjectMock = $this->createMock(Registration::class);
        $this->model = new CustomerRegistration($this->restrictionConfig);
    }

    public function testAfterIsRegistrationIsAllowedRestrictsRegistrationIfRestrictionModeForbidsIt()
    {
        $storeMock = $this->getMockBuilder(Store::class)
            ->addMethods(['isAdmin'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())->method('isAdmin')->willReturn(false);
        $this->restrictionConfig->expects(
            $this->any()
        )->method(
            'isRestrictionEnabled'
        )->willReturn(
            true
        );
        $this->restrictionConfig->expects(
            $this->once()
        )->method(
            'getMode'
        )->willReturn(
            Mode::ALLOW_NONE
        );
        $this->assertFalse($this->model->afterIsAllowed($this->subjectMock, true));
    }
}
