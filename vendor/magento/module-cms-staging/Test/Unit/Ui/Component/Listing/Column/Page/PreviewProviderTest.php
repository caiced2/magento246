<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Ui\Component\Listing\Column\Page;

use Magento\CmsStaging\Ui\Component\Listing\Column\Page\PreviewProvider;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreviewProviderTest extends TestCase
{
    /**
     * @var PreviewProvider
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $urlBuilderMock;

    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);
        $this->model = new PreviewProvider($this->urlBuilderMock);
    }

    /**
     * Test for method getUrl
     *
     * @dataProvider getUrlDataProvider
     * @param array $item
     * @param array $expected
     *
     * @return void
     */
    public function testGetUrl(array $item, array $expected): void
    {
        $url = 'preview_url';

        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with($expected['routePath'], $expected['routeParams'])
            ->willReturn($url);

        $this->assertEquals($url, $this->model->getUrl($item));
    }

    /**
     * DataProvider for testGetUrl
     *
     * @return array
     */
    public function getUrlDataProvider(): array
    {
        return [
            [
                'item' => [
                    '_first_store_id' => 'first_store_id',
                    'identifier' => 'identifier',
                ],
                'expected' => [
                    'routePath' => null,
                    'routeParams' => ['_direct' => 'identifier', '_nosid' => true]
                ],
            ],
            [
                'item' => [
                    '_first_store_id' => 'first_store_id',
                    'identifier' => 'identifier',
                    'store_id' => 1
                ],
                'expected' => [
                    'routePath' => 'identifier',
                    'routeParams' => ['_scope' => 1]
                ],
            ],
        ];
    }
}
