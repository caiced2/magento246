<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

use Magento\Support\Model\Report\Group\Logs\LogFilesData;
use Magento\Support\Model\Report\Group\Logs\TopDebugMessagesSection;

class TopDebugMessagesSectionTest extends AbstractLogsSectionTest
{
    /**
     * @var TopDebugMessagesSection
     */
    protected $topDebugMessagesSection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->topDebugMessagesSection = $this->objectManagerHelper->getObject(
            TopDebugMessagesSection::class,
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
        $topDebugMessagesData = [
            LogFilesData::DEBUG_MESSAGES => [
                [
                    6,
                    'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    '' . $currentDate . ', 14:18:33'
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/iphone-6s.html","invalidateInfo":{"identifier":"TARGET_RULE_2_1_1_0_0"},"is_exception":false} []',
                    '2015-09-22, 08:24:48'
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/admin/system_config/save/section/dev/store/1/","invalidateInfo":{"tags":["config_scopes","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    '2015-09-22, 09:28:07'
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/index/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    '2015-09-22, 10:14:34'
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/support/report/create/?isAjax=true","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    '' . $currentDate . ', 10:38:27'
                ]
            ]
        ];
        $expectedData = [
            (string)__('Top Debug Messages') => [
                'headers' => [__('Count'), __('Message'), __('Last Occurrence')],
                'data' => [
                    [
                        6,
                        'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                        '' . $currentDate . ', 14:18:33'
                    ],
                    [
                        1,
                        'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/iphone-6s.html","invalidateInfo":{"identifier":"TARGET_RULE_2_1_1_0_0"},"is_exception":false} []',
                        '2015-09-22, 08:24:48'
                    ],
                    [
                        1,
                        'cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/admin/system_config/save/section/dev/store/1/","invalidateInfo":{"tags":["config_scopes","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                        '2015-09-22, 09:28:07'
                    ],
                    [
                        1,
                        'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/index/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                        '2015-09-22, 10:14:34'
                    ],
                    [
                        1,
                        'cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/support/report/create/?isAjax=true","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                        '' . $currentDate . ', 10:38:27'
                    ]
                ]
            ]
        ];
        // @codingStandardsIgnoreEnd
        $this->logFilesDataMock->expects($this->once())->method('getLogFilesData')->willReturn($topDebugMessagesData);

        $this->assertEquals($expectedData, $this->topDebugMessagesSection->generate());
    }
}
