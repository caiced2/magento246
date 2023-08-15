<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Helper;

use Magento\CatalogEvent\Helper\Data;
use Magento\CatalogEvent\Model\Event;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CatalogEvent\Helper\Data
 */
class DataTest extends TestCase
{
    /**
     * @var Data
     */
    protected $data;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = (new ObjectManager($this))->getObject(Context::class);

        $this->data = new Data(
            $this->contextMock
        );
    }

    /**
     * @param string|bool|null $getImageResult
     * @param Invocation $getImageUrlCalls
     * @param string|bool $result
     * @return void
     * @dataProvider getEventImageUrlDataProvider
     */
    public function testGetEventImageUrl($getImageResult, $getImageUrlCalls, $result)
    {
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getImage'])
            ->onlyMethods(['getImageUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($getImageResult);

        $eventMock
            ->expects($getImageUrlCalls)
            ->method('getImageUrl')
            ->willReturn($result);

        $this->assertEquals($result, $this->data->getEventImageUrl($eventMock));
    }

    /**
     * @return array
     */
    public function getEventImageUrlDataProvider()
    {
        return [
            [null, $this->never(), false],
            [false, $this->never(), false],
            [0, $this->never(), false],
            ['data', $this->once(), 'data']
        ];
    }

    /**
     * @return void
     */
    public function testIsEnabled()
    {
        $this->contextMock
            ->getScopeConfig()
            ->expects($this->any())
            ->method('isSetFlag')
            ->with(Data::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn('result');

        $this->assertEquals('result', $this->data->isEnabled());
    }
}
