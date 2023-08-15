<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\Filter\ImageFieldFilter;
use Magento\Framework\Api\Data\ImageContentInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see ImageFieldFilter class
 */
class ImageFieldFilterTest extends TestCase
{
    /**
     * @var ImageFieldFilter
     */
    private ImageFieldFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new ImageFieldFilter();
    }

    public function testImageFieldsFiltered(): void
    {
        $inputEventData = [
            "images" => [
                [
                    "id" => "1",
                    "file" => "image.jpg",
                    ImageContentInterface::BASE64_ENCODED_DATA => "dGVzdA==",
                    'base64_data' => 'value'
                ],
                [
                    "id" => "2",
                    "position" => "1",
                    "data" => [
                        ImageContentInterface::BASE64_ENCODED_DATA => "dGVzdA==",
                        "name" => "test_image"
                    ]
                ]
            ]
        ];
        $filteredEventData = [
            "images" => [
                [
                    "id" => "1",
                    "file" => "image.jpg",
                    'base64_data' => 'value'
                ],
                [
                    "id" => "2",
                    "position" => "1",
                    "data" => [
                        "name" => "test_image"
                    ]
                ]
            ]
        ];

        self::assertEquals($filteredEventData, $this->filter->filter('some.event', $inputEventData));
    }
}
