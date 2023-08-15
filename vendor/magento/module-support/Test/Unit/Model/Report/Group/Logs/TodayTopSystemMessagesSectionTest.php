<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

use Magento\Support\Model\Report\Group\Logs\LogFilesData;
use Magento\Support\Model\Report\Group\Logs\TodayTopSystemMessagesSection;

class TodayTopSystemMessagesSectionTest extends AbstractLogsSectionTest
{
    /**
     * @var TodayTopSystemMessagesSection
     */
    protected $todayTopSystemMessagesSection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->todayTopSystemMessagesSection = $this->objectManagerHelper->getObject(
            TodayTopSystemMessagesSection::class,
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
        $todayTopSystemMessagesData = [
            LogFilesData::CURRENT_SYSTEM_MESSAGES => [
                [
                    2,
                    'Invalid template file: \'\' [] []',
                    '' . $currentDate . ', 16:26:30'
                ],
                [
                    1,
                    'Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []',
                    '' . $currentDate . ', 16:26:27'
                ],
                [
                    1,
                    'Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    '' . $currentDate . ', 16:26:27'
                ],
                [
                    1,
                    'Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    '' . $currentDate . ', 16:26:27'
                ]
            ]
        ];
        $expectedData = [
            (string)__('Today\'s Top System Messages') => [
                'headers' => [__('Count'), __('Message'), __('Last Occurrence')],
                'data' => [
                    [
                        2,
                        'Invalid template file: \'\' [] []',
                        '' . $currentDate . ', 16:26:30'
                    ],
                    [
                        1,
                        'Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []',
                        '' . $currentDate . ', 16:26:27'
                    ],
                    [
                        1,
                        'Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                        '' . $currentDate . ', 16:26:27'
                    ],
                    [
                        1,
                        'Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                        '' . $currentDate . ', 16:26:27'
                    ]
                ]
            ]
        ];
        // @codingStandardsIgnoreEnd
        $this->logFilesDataMock->expects($this->once())
            ->method('getLogFilesData')
            ->willReturn($todayTopSystemMessagesData);

        $this->assertEquals($expectedData, $this->todayTopSystemMessagesSection->generate());
    }
}
