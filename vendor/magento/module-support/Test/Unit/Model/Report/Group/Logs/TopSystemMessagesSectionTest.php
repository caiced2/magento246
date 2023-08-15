<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

use Magento\Support\Model\Report\Group\Logs\LogFilesData;
use Magento\Support\Model\Report\Group\Logs\TopSystemMessagesSection;

class TopSystemMessagesSectionTest extends AbstractLogsSectionTest
{
    /**
     * @var TopSystemMessagesSection
     */
    protected $topSystemMessagesSection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->topSystemMessagesSection = $this->objectManagerHelper->getObject(
            TopSystemMessagesSection::class,
            [
                'logFilesData' => $this->logFilesDataMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGenerate()
    {
        $currentDate = (new \DateTime())->format('Y-m-d');
        // @codingStandardsIgnoreStart
        $topSystemMessagesData = [
            LogFilesData::SYSTEM_MESSAGES => [
                [
                    3,
                    'Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []',
                    '' . $currentDate . ', 16:26:27'
                ],
                [
                    3,
                    'Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    '' . $currentDate . ', 16:26:27'
                ],
                [
                    3,
                    'Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    '' . $currentDate . ', 16:26:27'
                ],
                [
                    3,
                    'Invalid template file: \'\' [] []',
                    '' . $currentDate . ', 16:26:30'
                ]
            ]
        ];
        $expectedData = [
            (string)__('Top System Messages') => [
                'headers' => [__('Count'), __('Message'), __('Last Occurrence')],
                'data' => [
                    [
                        3,
                        'Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []',
                        '' . $currentDate . ', 16:26:27'
                    ],
                    [
                        3,
                        'Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                        '' . $currentDate . ', 16:26:27'
                    ],
                    [
                        3,
                        'Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                        '' . $currentDate . ', 16:26:27'
                    ],
                    [
                        3,
                        'Invalid template file: \'\' [] []',
                        '' . $currentDate . ', 16:26:30'
                    ]
                ]
            ]
        ];
        // @codingStandardsIgnoreEnd
        $this->logFilesDataMock->expects($this->once())->method('getLogFilesData')->willReturn($topSystemMessagesData);

        $this->assertEquals($expectedData, $this->topSystemMessagesSection->generate());
    }
}
