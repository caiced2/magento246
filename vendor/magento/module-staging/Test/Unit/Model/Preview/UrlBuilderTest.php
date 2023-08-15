<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Preview;

use Magento\Framework\Url;
use Magento\Framework\UrlInterface;
use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UrlBuilderTest extends TestCase
{
    private const STORE_CODE = 'test';
    /**
     * @var MockObject
     */
    private $coreUrlBuilderMock;

    /**
     * @var MockObject
     */
    private $frontendUrlMock;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    protected function setUp(): void
    {
        $this->coreUrlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);
        $this->frontendUrlMock = $this->createMock(Url::class);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')
            ->willReturn(
                $this->createConfiguredMock(
                    StoreInterface::class,
                    [
                        'getCode' => self::STORE_CODE
                    ]
                )
            );
        $this->urlBuilder = new UrlBuilder(
            $this->coreUrlBuilderMock,
            $this->frontendUrlMock,
            $storeManager
        );
    }

    public function testGetPreviewUrl()
    {
        $baseUrl = 'http://www.example.com';
        $versionId = 1;
        $this->coreUrlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                UrlBuilder::URL_PATH_PREVIEW,
                [
                    '_query' => [
                        UrlBuilder::PARAM_PREVIEW_VERSION => $versionId,
                        UrlBuilder::PARAM_PREVIEW_URL => $baseUrl
                    ]
                ]
            );
        $this->urlBuilder->getPreviewUrl($versionId, $baseUrl);
    }

    public function testGetFrontendPreviewUrl()
    {
        $baseUrl = 'http://www.example.com';
        $versionId = 1;
        $this->frontendUrlMock->expects($this->once())->method('getUrl')->willReturn($baseUrl);
        $this->coreUrlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                UrlBuilder::URL_PATH_PREVIEW,
                [
                    '_query' => [
                        UrlBuilder::PARAM_PREVIEW_VERSION => $versionId,
                        UrlBuilder::PARAM_PREVIEW_URL => $baseUrl
                    ],
                ]
            );
        $this->urlBuilder->getPreviewUrl($versionId);
    }

    public function testShouldAddStoreCodeToTheUrl(): void
    {
        $baseUrl = 'http://www.example.com';
        $versionId = 1;
        $this->coreUrlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                UrlBuilder::URL_PATH_PREVIEW,
                [
                    '_query' => [
                        UrlBuilder::PARAM_PREVIEW_VERSION => $versionId,
                        UrlBuilder::PARAM_PREVIEW_URL => $baseUrl,
                        UrlBuilder::PARAM_PREVIEW_STORE => self::STORE_CODE
                    ],
                ]
            );
        $this->urlBuilder->getPreviewUrl($versionId, $baseUrl, 1);
    }
}
