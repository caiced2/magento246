<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\GiftRegistry\Observer\AddressFormat;
use Magento\GiftRegistry\Observer\AddressFormatFront;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressFormatFrontTest extends TestCase
{
    /**
     * @var AddressFormatFront
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $addressFormatMock;

    protected function setUp(): void
    {
        $this->addressFormatMock = $this->createMock(AddressFormat::class);
        $this->model = new AddressFormatFront($this->addressFormatMock);
    }

    public function testexecute()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->addressFormatMock->expects($this->once())->method('format')->with($observerMock)->willReturnSelf();
        $this->assertEquals($this->model, $this->model->execute($observerMock));
    }
}
