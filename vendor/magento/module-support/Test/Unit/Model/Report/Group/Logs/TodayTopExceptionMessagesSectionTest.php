<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

use Magento\Support\Model\Report\Group\Logs\LogFilesData;
use Magento\Support\Model\Report\Group\Logs\TodayTopExceptionMessagesSection;

class TodayTopExceptionMessagesSectionTest extends AbstractLogsSectionTest
{
    /**
     * @var TodayTopExceptionMessagesSection
     */
    protected $todayTopExceptionMessagesSection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->todayTopExceptionMessagesSection = $this->objectManagerHelper->getObject(
            TodayTopExceptionMessagesSection::class,
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
        $todayTopExceptionMessagesData = [
            LogFilesData::CURRENT_EXCEPTION_MESSAGES => [
                [
                    3,
                    '\'Exception\' with message \'Exception from \Magento\Catalog\Controller\Adminhtml\Product\Builder::build\' in C:\Magento\Catalog\Controller\Adminhtml\Product\Builder.php:77',
                    '#0 [internal function]: Magento\Catalog\Controller\Adminhtml\Product\Builder->build(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
                    . '#1 C:\Magento\Framework\Interception\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
                    . '#2 C:\Magento\Framework\Interception\Chain\Chain.php(70): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
                    . '#3 C:\Magento\Framework\Interception\Interceptor.php(136): Magento\Framework\Interception\Chain\Chain->invokeNext(\'Magento\Catalog...\', \'build\', Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Array, \'configurable\')' . PHP_EOL,
                    '' . $currentDate . ', 07:58:34'
                ],
            ]
        ];
        $expectedData = [
            (string)__('Today\'s Top Exception Messages') => [
                'headers' => [__('Count'), __('Message'), __('Stack Trace'), __('Last Occurrence')],
                'data' => [
                    [
                        3,
                        '\'Exception\' with message \'Exception from \Magento\Catalog\Controller\Adminhtml\Product\Builder::build\' in C:\Magento\Catalog\Controller\Adminhtml\Product\Builder.php:77',
                        '#0 [internal function]: Magento\Catalog\Controller\Adminhtml\Product\Builder->build(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
                        . '#1 C:\Magento\Framework\Interception\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
                        . '#2 C:\Magento\Framework\Interception\Chain\Chain.php(70): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
                        . '#3 C:\Magento\Framework\Interception\Interceptor.php(136): Magento\Framework\Interception\Chain\Chain->invokeNext(\'Magento\Catalog...\', \'build\', Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Array, \'configurable\')' . PHP_EOL,
                        '' . $currentDate . ', 07:58:34'
                    ]
                ]
            ]
        ];
        // @codingStandardsIgnoreEnd
        $this->logFilesDataMock->expects($this->once())
            ->method('getLogFilesData')
            ->willReturn($todayTopExceptionMessagesData);

        $this->assertEquals($expectedData, $this->todayTopExceptionMessagesSection->generate());
    }
}
