<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Block\Adminhtml\Event;

use Magento\Backend\Block\Widget\Context;
use Magento\CatalogEvent\Block\Adminhtml\Event\Edit;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CatalogEvent\Block\Adminhtml\Event\Edit
 */
class EditTest extends TestCase
{
    /**
     * @var Edit
     */
    protected $edit;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = (new ObjectManager($this))->getObject(Context::class);
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->edit = new Edit(
            $this->contextMock,
            $this->registryMock
        );
    }

    /**
     * @return void
     */
    public function testGetEvent()
    {
        $this->registryMock
            ->expects($this->once())
            ->method('registry')
            ->with('magento_catalogevent_event')
            ->willReturn('result');

        $this->assertEquals('result', $this->edit->getEvent());
    }
}
