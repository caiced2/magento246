<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Ui\Component\Listing\Column\Product;

use Magento\CatalogStaging\Ui\Component\Listing\Column\Product\UrlProvider;
use Magento\Framework\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UrlProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $urlBuilderMock;

    /**
     * @var UrlProvider
     */
    private $urlProvider;

    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->createMock(Url::class);
        $this->urlProvider = new UrlProvider(
            $this->urlBuilderMock
        );
    }

    public function testGetUrl()
    {
        $item = [
            'entity_id' => 1,
        ];
        $this->urlBuilderMock->expects($this->once())->method('getUrl')->with(
            'catalog/product/view',
            [
                'id' => $item['entity_id'],
                '_nosid' => true,

            ]
        );

        $this->urlProvider->getUrl($item);
    }
}
