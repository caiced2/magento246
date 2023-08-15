<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Test\Unit\Model\Config\ContentType\AdditionalData\Provider;

use Magento\BannerPageBuilder\Model\Config\ContentType\AdditionalData\Provider\DynamicBlockDataUrl;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test basic functionality of the DynamicBlockDataUrl class
 */
class DynamicBlockDataUrlTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetData()
    {
        $urlMock = $this->getMockBuilder(UrlInterface::class)->getMock();
        $urlMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('pagebuilder/contenttype_dynamicblock/metadata')
            ->willReturn('foo');

        $dataUrl = new DynamicBlockDataUrl($urlMock);
        $actual = $dataUrl->getData('bar');

        $this->assertSame(['bar' => 'foo'], $actual);
    }
}
