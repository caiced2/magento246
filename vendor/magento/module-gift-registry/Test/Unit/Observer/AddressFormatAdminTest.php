<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\DesignInterface;
use Magento\GiftRegistry\Observer\AddressFormat;
use Magento\GiftRegistry\Observer\AddressFormatAdmin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressFormatAdminTest extends TestCase
{
    /**
     * @var AddressFormatAdmin
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $addressFormatMock;

    /**
     * @var MockObject
     */
    protected $designMock;

    protected function setUp(): void
    {
        $this->addressFormatMock = $this->createMock(AddressFormat::class);
        $this->designMock = $this->getMockForAbstractClass(DesignInterface::class);
        $this->model = new AddressFormatAdmin(
            $this->addressFormatMock,
            $this->designMock
        );
    }

    public function testexecute()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->designMock->expects($this->once())->method('getArea')->willReturn(Area::AREA_FRONTEND);
        $this->addressFormatMock->expects($this->once())->method('format')->with($observerMock)->willReturnSelf();
        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
