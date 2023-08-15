<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

use Magento\Support\Model\Report\Group\Logs\LogFilesData;
use Magento\Support\Model\Report\Group\Logs\TopExceptionMessagesSection;

class TopExceptionMessagesSectionTest extends AbstractLogsSectionTest
{
    /**
     * @var TopExceptionMessagesSection
     */
    protected $topExceptionMessagesSection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->topExceptionMessagesSection = $this->objectManagerHelper->getObject(
            TopExceptionMessagesSection::class,
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
        $topExceptionMessagesData = [
            LogFilesData::EXCEPTION_MESSAGES => [
                [
                    4,
                    '\'Exception\' with message \'Exception from \Magento\Catalog\Controller\Adminhtml\Product\Builder::build\' in C:\Magento\Catalog\Controller\Adminhtml\Product\Builder.php:77',
                    '#0 [internal function]: Magento\Catalog\Controller\Adminhtml\Product\Builder->build(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
                    . '#1 C:\Magento\Framework\Interception\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
                    . '#2 C:\Magento\Framework\Interception\Chain\Chain.php(70): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
                    . '#3 C:\Magento\Framework\Interception\Interceptor.php(136): Magento\Framework\Interception\Chain\Chain->invokeNext(\'Magento\Catalog...\', \'build\', Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Array, \'configurable\')' . PHP_EOL,
                    '' . $currentDate . ', 07:58:34'
                ],
                [
                    1,
                    '\'Magento\Framework\Exception\LocalizedException\' with message \'Very bad exception.\' in C:\Magento\Support\Model\Report.php:144',
                    '#0 C:\Magento\Support\Block\Adminhtml\Report\View\Tabs.php(69): Magento\Support\Model\Report->prepareReportData()' . PHP_EOL
                    . '#1 C:\Magento\Framework\View\Element\AbstractBlock.php(257): Magento\Support\Block\Adminhtml\Report\View\Tabs->_prepareLayout()' . PHP_EOL
                    . '#2 C:\Magento\Framework\View\Layout\Generator\Block.php(139): Magento\Framework\View\Element\AbstractBlock->setLayout(Object(Magento\Framework\View\Layout\Interceptor))' . PHP_EOL
                    . '#3 C:\Magento\Framework\View\Layout\GeneratorPool.php(86): Magento\Framework\View\Layout\Generator\Block->process(Object(Magento\Framework\View\Layout\Reader\Context), Object(Magento\Framework\View\Layout\Generator\Context))' . PHP_EOL
                    . '#4 C:\Magento\Framework\View\Layout.php(329): Magento\Framework\View\Layout\GeneratorPool->process(Object(Magento\Framework\View\Layout\Reader\Context), Object(Magento\Framework\View\Layout\Generator\Context))' . PHP_EOL
                    . '#5 C:\Magento\Framework\View\Layout\Interceptor.php(89): Magento\Framework\View\Layout->generateElements()' . PHP_EOL,
                    '2015-09-17, 09:04:58'
                ]
            ]
        ];
        $expectedData = [
            (string)__('Top Exception Messages') => [
                'headers' => [__('Count'), __('Message'), __('Stack Trace'), __('Last Occurrence')],
                'data' => [
                    [
                        4,
                        '\'Exception\' with message \'Exception from \Magento\Catalog\Controller\Adminhtml\Product\Builder::build\' in C:\Magento\Catalog\Controller\Adminhtml\Product\Builder.php:77',
                        '#0 [internal function]: Magento\Catalog\Controller\Adminhtml\Product\Builder->build(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
                        . '#1 C:\Magento\Framework\Interception\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
                        . '#2 C:\Magento\Framework\Interception\Chain\Chain.php(70): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
                        . '#3 C:\Magento\Framework\Interception\Interceptor.php(136): Magento\Framework\Interception\Chain\Chain->invokeNext(\'Magento\Catalog...\', \'build\', Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Array, \'configurable\')' . PHP_EOL,
                        '' . $currentDate . ', 07:58:34'
                    ],
                    [
                        1,
                        '\'Magento\Framework\Exception\LocalizedException\' with message \'Very bad exception.\' in C:\Magento\Support\Model\Report.php:144',
                        '#0 C:\Magento\Support\Block\Adminhtml\Report\View\Tabs.php(69): Magento\Support\Model\Report->prepareReportData()' . PHP_EOL
                        . '#1 C:\Magento\Framework\View\Element\AbstractBlock.php(257): Magento\Support\Block\Adminhtml\Report\View\Tabs->_prepareLayout()' . PHP_EOL
                        . '#2 C:\Magento\Framework\View\Layout\Generator\Block.php(139): Magento\Framework\View\Element\AbstractBlock->setLayout(Object(Magento\Framework\View\Layout\Interceptor))' . PHP_EOL
                        . '#3 C:\Magento\Framework\View\Layout\GeneratorPool.php(86): Magento\Framework\View\Layout\Generator\Block->process(Object(Magento\Framework\View\Layout\Reader\Context), Object(Magento\Framework\View\Layout\Generator\Context))' . PHP_EOL
                        . '#4 C:\Magento\Framework\View\Layout.php(329): Magento\Framework\View\Layout\GeneratorPool->process(Object(Magento\Framework\View\Layout\Reader\Context), Object(Magento\Framework\View\Layout\Generator\Context))' . PHP_EOL
                        . '#5 C:\Magento\Framework\View\Layout\Interceptor.php(89): Magento\Framework\View\Layout->generateElements()' . PHP_EOL,
                        '2015-09-17, 09:04:58'
                    ]
                ]
            ]
        ];
        // @codingStandardsIgnoreEnd
        $this->logFilesDataMock->expects($this->once())
            ->method('getLogFilesData')
            ->willReturn($topExceptionMessagesData);

        $this->assertEquals($expectedData, $this->topExceptionMessagesSection->generate());
    }
}
