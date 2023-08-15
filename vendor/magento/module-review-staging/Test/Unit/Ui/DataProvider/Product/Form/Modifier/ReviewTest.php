<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewStaging\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\ReviewStaging\Ui\DataProvider\Product\Form\Modifier\Review;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReviewTest extends TestCase
{
    /**
     * @var Review
     */
    private $model;

    /**
     * @var MockObject
     */
    private $reviewModifierMock;

    protected function setUp(): void
    {
        $this->reviewModifierMock = $this->createPartialMock(
            \Magento\Review\Ui\DataProvider\Product\Form\Modifier\Review::class,
            ['modifyData', 'modifyMeta']
        );
        $this->model = new Review(
            $this->reviewModifierMock
        );
    }

    public function testModifyData()
    {
        $data = ['key' => 'value'];
        $this->reviewModifierMock->expects($this->once())->method('modifyData')->with($data)->willReturn($data);
        $this->assertEquals($data, $this->model->modifyData($data));
    }

    public function testModifyMeta()
    {
        $meta = [
            'review' => [
                'children' => [
                    'review_listing' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => 'dataScope',
                                    'externalProvider' => 'externalProvider',
                                    'selectionsProvider' => 'selectionsProvider',
                                    'ns' => 'ns',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $modifiedMeta = [
            'review' => [
                'children' => [
                    'stagingreview_listing' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => 'stagingreview_listing',
                                    'externalProvider' => 'stagingreview_listing.stagingreview_listing_data_source',
                                    'selectionsProvider' => 'stagingreview_listing.stagingreview_listing.' .
                                        'product_columns.ids',
                                    'ns' => 'stagingreview_listing',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->reviewModifierMock->expects($this->once())->method('modifyMeta')->with($meta)->willReturn($meta);
        $this->assertEquals($modifiedMeta, $this->model->modifyMeta($meta));
    }

    public function testModifyMetaWithDisabledReview()
    {
        $meta = ['key' => 'value'];

        $this->reviewModifierMock->expects($this->once())->method('modifyMeta')->with($meta)->willReturn($meta);
        $this->assertEquals($meta, $this->model->modifyMeta($meta));
    }
}
