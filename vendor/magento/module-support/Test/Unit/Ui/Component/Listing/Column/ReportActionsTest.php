<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Support\Ui\Component\Listing\Column\ReportActions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReportActionsTest extends TestCase
{
    public function testPrepareItemsByReportId()
    {
        $objectManager = new ObjectManager($this);
        /** @var UrlInterface|MockObject */
        $urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->never())->method('getProcessor')->willReturn($processor);

        /** @var ReportActions $model */
        $model = $objectManager->getObject(
            ReportActions::class,
            [
                'urlBuilder' => $urlBuilderMock,
                'context' => $contextMock,
            ]
        );

        // Define test input and expectations
        $reportId = 1;
        $items = [
            'data' => [
                'items' => [
                    [
                        'report_id' => $reportId
                    ]
                ]
            ]
        ];
        $name = 'item_name';
        $expectedItems = [
            [
                'report_id' => $reportId,
                $name => [
                    'view' => [
                        'href' => 'support/report/view',
                        'label' => __('View')
                    ],
                    'delete' => [
                        'href' => 'support/report/delete',
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', ['${ $.$data.report_id }']),
                            'message' => __(
                                'Are you sure you want to delete a "%1" record?',
                                ['${ $.$data.report_id }']
                            ),
                            '__disableTmpl' => ['title' => false, 'message' => false]
                        ]
                    ],
                    'download' => [
                        'href' => 'support/report/download',
                        'label' => __('Download')
                    ]
                ],
                'report_data' => null
            ]
        ];
        // Configure mocks and object data
        $urlBuilderMock->expects($this->any())
            ->method('getUrl')
            ->willReturnMap(
                [
                    [
                        ReportActions::REPORT_URL_PATH_VIEW,
                        [
                            'id' => $reportId
                        ],
                        'support/report/view',
                    ],
                    [
                        ReportActions::REPORT_URL_PATH_DELETE,
                        [
                            'id' => $reportId
                        ],
                        'support/report/delete',
                    ],
                    [
                        ReportActions::REPORT_URL_PATH_DOWNLOAD,
                        [
                            'id' => $reportId
                        ],
                        'support/report/download',
                    ]
                ]
            );
        $model->setName($name);
        $items = $model->prepareDataSource($items);
        $this->assertEquals($expectedItems, $items['data']['items']);
    }
}
