<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Block\Customer\Edit;

use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\GiftRegistry\Block\Customer\Date;
use Magento\GiftRegistry\Block\Customer\Edit\AbstractEdit;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractEditTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $contextMock;

    /**
     * @var MockObject
     */
    protected $localeDateMock;

    /**
     * @var MockObject
     */
    protected $layoutMock;

    /**
     * @var AbstractEdit
     */
    protected $block;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->localeDateMock = $this->getMockForAbstractClass(TimezoneInterface::class);
        $this->layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->contextMock->expects($this->any())->method('getLayout')->willReturn($this->layoutMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getLocaleDate')
            ->willReturn($this->localeDateMock);
        $requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($requestMock);
        $assertRepoMock = $this->createMock(Repository::class);
        $this->contextMock
            ->expects($this->once())
            ->method('getAssetRepository')
            ->willReturn($assertRepoMock);

        $assertRepoMock->expects($this->once())->method('getUrlWithParams');
        $this->block = $this->getMockForAbstractClass(
            AbstractEdit::class,
            [
                $this->contextMock,
                $this->createMock(Data::class),
                $this->getMockForAbstractClass(EncoderInterface::class),
                $this->createMock(Config::class),
                $this->createPartialMock(
                    CollectionFactory::class,
                    ['create']
                ),
                $this->createPartialMock(
                    \Magento\Directory\Model\ResourceModel\Country\CollectionFactory::class,
                    ['create']
                ),
                $this->createMock(Registry::class),
                $this->createMock(Session::class),
                $this->createMock(\Magento\GiftRegistry\Model\Attribute\Config::class),
                []
            ]
        );
    }

    public function testGetCalendarDateHtml()
    {
        $value = '07/24/14';
        $dateTime = new \DateTime($value);
        $block = $this->getMockBuilder(Date::class)
            ->addMethods(['setId', 'setName', 'setValue', 'setClass', 'setImage', 'setDateFormat'])
            ->onlyMethods(['getHtml'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeDateMock
            ->expects($this->once())
            ->method('formatDateTime')
            ->with($dateTime, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE)
            ->willReturn($value);
        $this->localeDateMock
            ->expects($this->once())
            ->method('getDateFormat')
            ->with(\IntlDateFormatter::MEDIUM)
            ->willReturn('format');
        $this->layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(Date::class)->willReturn($block);
        $block->expects($this->once())->method('setId')->with('id')->willReturnSelf();
        $block->expects($this->once())->method('setName')->with('name')->willReturnSelf();
        $block->expects($this->once())->method('setValue')->with($value)->willReturnSelf();
        $block->expects($this->once())
            ->method('setClass')
            ->with(' product-custom-option datetime-picker input-text validate-date')->willReturnSelf();
        $block->expects($this->once())
            ->method('setImage')->willReturnSelf();
        $block->expects($this->once())
            ->method('setDateFormat')
            ->with('format')->willReturnSelf();
        $block->expects($this->once())->method('getHtml')->willReturn('expected_html');
        $this->assertEquals('expected_html', $this->block->getCalendarDateHtml('name', 'id', $value));
    }
}
