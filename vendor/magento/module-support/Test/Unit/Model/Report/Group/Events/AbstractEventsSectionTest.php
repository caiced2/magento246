<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Events;

use Magento\Framework\Event\Config\Reader;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Group\Events\AbstractEventsSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractEventsSectionTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var AbstractEventsSection
     */
    protected $eventsSection;

    /**
     * @var Reader|MockObject
     */
    protected $readerMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();
        $this->readerMock = $this->getMockBuilder(Reader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock
            ->expects($this->any())
            ->method('create')
            ->with(Reader::class)
            ->willReturn($this->readerMock);
        $this->eventsSection = $this->objectManagerHelper->getObject($this->getSectionName(), [
            'logger' => $this->loggerMock,
            'objectManager' => $this->objectManagerMock,
        ]);
    }

    /**
     * @return string
     */
    abstract protected function getExpectedTitle();

    /**
     * @return string|null
     */
    abstract protected function getExpectedType();

    /**
     * @return string
     */
    abstract protected function getExpectedAreaCode();

    /**
     * @return string
     */
    abstract protected function getSectionName();

    /**
     * @return AbstractEventsSection
     */
    protected function getSection()
    {
        return $this->eventsSection;
    }

    /**
     * @return void
     */
    public function testGetTitle()
    {
        $this->assertSame($this->getExpectedTitle(), $this->getSection()->getTitle());
    }

    /**
     * @return void
     */
    public function testGetType()
    {
        $this->assertSame($this->getExpectedType(), $this->getSection()->getType());
    }

    /**
     * @return void
     */
    public function testGetExpectedAreaCode()
    {
        $this->assertSame($this->getExpectedAreaCode(), $this->getSection()->getAreaCode());
    }
}
