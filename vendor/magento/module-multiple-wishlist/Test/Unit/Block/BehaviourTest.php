<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Block;

use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\MultipleWishlist\Block\Behaviour;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Magento\MultipleWishlist\Block\Behaviour
 */
class BehaviourTest extends TestCase
{
    const CREATE_WISHLIST_ROUTE = 'wishlist/index/createwishlist';

    /**
     * @var Behaviour
     */
    protected $blockBehaviour;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);
        $objectManager = new ObjectManager($this);
        $this->blockBehaviour = $objectManager->getObject(
            Behaviour::class,
            [
                'context' => $this->contextMock
            ]
        );
    }

    /**
     * @covers \Magento\MultipleWishlist\Block\Behaviour::getCreateUrl
     * @param bool $isSecure
     * @param string $url
     * @param string $expectedResult
     * @dataProvider getCreateUrlDataProvider
     */
    public function testGetCreateUrl($isSecure, $url, $expectedResult)
    {
        $this->requestMock->expects($this->once())
            ->method('isSecure')
            ->willReturn($isSecure);
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                self::CREATE_WISHLIST_ROUTE,
                [
                    '_secure' => $isSecure
                ]
            )
            ->willReturn($url);

        $this->assertStringStartsWith($expectedResult, $this->blockBehaviour->getCreateUrl());
    }

    public function getCreateUrlDataProvider()
    {
        return [
            'http' => [
                'isSecure' => false,
                'url' => 'http://site-name.com/wishlist/index/createwishlist',
                'expectedResult' => 'http://'
            ],
            'https' => [
                'isSecure' => true,
                'url' => 'https://site-name.com/wishlist/index/createwishlist',
                'expectedResult' => 'https://'
            ]
        ];
    }
}
