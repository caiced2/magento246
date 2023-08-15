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
use Magento\Support\Ui\Component\Listing\Column\BackupActions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackupActionsTest extends TestCase
{
    public function testPrepareItemsByBackupId()
    {
        $objectManager = new ObjectManager($this);
        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->never())->method('getProcessor')->willReturn($processor);
        /** @var UrlInterface|MockObject */
        $urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        /** @var BackupActions $model */
        $model = $objectManager->getObject(
            BackupActions::class,
            [
                'urlBuilder' => $urlBuilderMock,
                'context' => $contextMock,
            ]
        );

        // Define test input and expectations
        $backupId = 1;
        $items = [
            'data' => [
                'items' => [
                    [
                        'backup_id' => $backupId
                    ]
                ]
            ]
        ];
        $name = 'item_name';
        $expectedItems = [
            [
                'backup_id' => $backupId,
                $name => [
                    'log' => [
                        'href' => 'support/backup/log',
                        'label' => __('Show Log')
                    ],
                    'delete' => [
                        'href' => 'support/backup/delete',
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete "%1"', ['${ $.$data.backup_id }']),
                            'message' => __(
                                'Are you sure you want to delete a "%1" record?',
                                ['${ $.$data.backup_id }']
                            ),
                            '__disableTmpl' => ['title' => false, 'message' => false]
                        ]
                    ]
                ],
            ]
        ];
        // Configure mocks and object data
        $urlBuilderMock->expects($this->any())
            ->method('getUrl')
            ->willReturnMap(
                [
                    [
                        BackupActions::BACKUP_URL_PATH_SHOW_LOG,
                        [
                            'id' => $backupId
                        ],
                        'support/backup/log',
                    ],
                    [
                        BackupActions::BACKUP_URL_PATH_DELETE,
                        [
                            'id' => $backupId
                        ],
                        'support/backup/delete',
                    ],
                ]
            );

        $model->setName($name);
        $items = $model->prepareDataSource($items);

        $this->assertEquals($expectedItems, $items['data']['items']);
    }
}
