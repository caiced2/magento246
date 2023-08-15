<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Support\Model\DataFormatter;
use Magento\Support\Model\Report\Group\Logs\LogFilesData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LogFilesDataTest extends TestCase
{
    /**
     * @var LogFilesData
     */
    private $logsFileData;

    /**
     * @var Read|MockObject
     */
    private $directoryReadMock;

    /**
     * @var \Magento\Framework\Filesystem\File\Read|MockObject
     */
    private $fileReadMock;

    /**
     * @var DataFormatter|MockObject
     */
    private $dataFormatterMock;

    /**
     * @var Filesystem|MockObject
     */
    private $fileSystemMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|MockObject
     */
    private $dateTimeMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->directoryReadMock = $this->getMockBuilder(Read::class)
            ->disableOriginalConstructor()
            ->setMethods(['read', 'isFile', 'openFile', 'isReadable'])
            ->getMock();
        $this->fileReadMock = $this->createMock(\Magento\Framework\Filesystem\File\Read::class);
        $this->dataFormatterMock = $this->createMock(DataFormatter::class);
        $this->fileSystemMock = $this->createMock(Filesystem::class);
        $this->fileSystemMock->expects($this->atLeastOnce())
            ->method('getDirectoryRead')
            ->willReturn($this->directoryReadMock);
        $this->dateTimeMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['date'])
            ->getMock();
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->logsFileData = new LogFilesData(
            $this->fileSystemMock,
            $this->dataFormatterMock,
            $this->dateTimeMock,
            $this->loggerMock
        );
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGenerate()
    {
        $currentDate = (new \DateTime())->format('Y-m-d');
        $stat = [
            'debug.log' => ['size' => 42000, 'mtime' => 1735689600],
            'exception.log' => ['size' => 42000, 'mtime' => 1735689600],
            'empty.log' => ['size' => 0, 'mtime' => 1735689600],
            'system.log' => ['size' => 42000, 'mtime' => 1735689600],
        ];
        $logFiles = array_keys($stat);
        $modifiedDate = date('r', 1735689600);
        $dateTimeMap = [
            ['Y-m-d', null, $currentDate],
            ['r', 1735689600, $modifiedDate]
        ];
        $formattedSize = '42 kB';
        // @codingStandardsIgnoreStart
        $fileContent = [
            '[2015-09-22 08:24:48] main.DEBUG: cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/iphone-6s.html","invalidateInfo":{"identifier":"TARGET_RULE_2_1_1_0_0"},"is_exception":false} []' . PHP_EOL
            . '[2015-09-22 09:28:07] main.DEBUG: cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/admin/system_config/save/section/dev/store/1/","invalidateInfo":{"tags":["config_scopes","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            . '[2015-09-22 10:14:34] main.DEBUG: cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/index/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            . '[2015-09-22 10:16:26] main.DEBUG: cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            . '[2015-09-22 10:34:17] main.DEBUG: cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            . '[' . $currentDate . ' 10:38:27] main.DEBUG: cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/support/report/create/?isAjax=true","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            . '[' . $currentDate . ' 10:39:22] main.CRITICAL: Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
            . '[' . $currentDate . ' 10:48:29] main.DEBUG: cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            . '[' . $currentDate . ' 13:16:42] main.DEBUG: cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            . '[' . $currentDate . ' 13:51:17] main.DEBUG: cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            . '[' . $currentDate . ' 14:12:22] main.CRITICAL: Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
            . '[' . $currentDate . ' 14:18:33] main.DEBUG: cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []' . PHP_EOL
            ,
            '[2015-09-17 09:04:58] main.CRITICAL: exception \'Magento\Framework\Exception\LocalizedException\' with message \'Very bad exception.\' in C:\Magento\Support\Model\Report.php:144' . PHP_EOL
            . 'Stack trace:' . PHP_EOL
            . '#0 C:\Magento\Support\Block\Adminhtml\Report\View\Tabs.php(69): Magento\Support\Model\Report->prepareReportData()' . PHP_EOL
            . '#1 C:\Magento\Framework\View\Element\AbstractBlock.php(257): Magento\Support\Block\Adminhtml\Report\View\Tabs->_prepareLayout()' . PHP_EOL
            . '#2 C:\Magento\Framework\View\Layout\Generator\Block.php(139): Magento\Framework\View\Element\AbstractBlock->setLayout(Object(Magento\Framework\View\Layout\Interceptor))' . PHP_EOL
            . '#3 C:\Magento\Framework\View\Layout\GeneratorPool.php(86): Magento\Framework\View\Layout\Generator\Block->process(Object(Magento\Framework\View\Layout\Reader\Context), Object(Magento\Framework\View\Layout\Generator\Context))' . PHP_EOL
            . '#4 C:\Magento\Framework\View\Layout.php(329): Magento\Framework\View\Layout\GeneratorPool->process(Object(Magento\Framework\View\Layout\Reader\Context), Object(Magento\Framework\View\Layout\Generator\Context))' . PHP_EOL
            . '#5 C:\Magento\Framework\View\Layout\Interceptor.php(89): Magento\Framework\View\Layout->generateElements()' . PHP_EOL
            . '#6 {main} [] []' . PHP_EOL
            . '[2015-09-21 07:58:08] main.CRITICAL: exception \'Exception\' with message \'Exception from \Magento\Catalog\Controller\Adminhtml\Product\Builder::build\' in C:\Magento\Catalog\Controller\Adminhtml\Product\Builder.php:77' . PHP_EOL
            . 'Stack trace:' . PHP_EOL
            . '#0 [internal function]: Magento\Catalog\Controller\Adminhtml\Product\Builder->build(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#1 C:\Magento\Framework\Interception\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
            . '#2 C:\Magento\Framework\Interception\Chain\Chain.php(70): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
            . '#3 C:\Magento\Framework\Interception\Interceptor.php(136): Magento\Framework\Interception\Chain\Chain->invokeNext(\'Magento\Catalog...\', \'build\', Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Array, \'configurable\')' . PHP_EOL
            . '#4 C:\Magento\ConfigurableProduct\Controller\Adminhtml\Product\Builder\Plugin.php(48): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->Magento\Framework\Interception\{closure}(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#5 [internal function]: Magento\ConfigurableProduct\Controller\Adminhtml\Product\Builder\Plugin->aroundBuild(Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Object(Closure), Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#6 {main} [] []' . PHP_EOL
            . '[' . $currentDate . ' 07:58:28] main.CRITICAL: exception \'Exception\' with message \'Exception from \Magento\Catalog\Controller\Adminhtml\Product\Builder::build\' in C:\Magento\Catalog\Controller\Adminhtml\Product\Builder.php:77' . PHP_EOL
            . 'Stack trace:' . PHP_EOL
            . '#0 [internal function]: Magento\Catalog\Controller\Adminhtml\Product\Builder->build(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#1 C:\Magento\Framework\Interception\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
            . '#2 C:\Magento\Framework\Interception\Chain\Chain.php(70): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
            . '#3 C:\Magento\Framework\Interception\Interceptor.php(136): Magento\Framework\Interception\Chain\Chain->invokeNext(\'Magento\\Catalog...\', \'build\', Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Array, \'configurable\')' . PHP_EOL
            . '#4 C:\Magento\ConfigurableProduct\Controller\Adminhtml\Product\Builder\Plugin.php(48): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->Magento\Framework\Interception\{closure}(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#5 [internal function]: Magento\ConfigurableProduct\Controller\Adminhtml\Product\Builder\Plugin->aroundBuild(Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Object(Closure), Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#6 {main} [] []' . PHP_EOL
            . '[' . $currentDate . ' 07:58:31] main.CRITICAL: exception \'Exception\' with message \'Exception from \Magento\Catalog\Controller\Adminhtml\Product\Builder::build\' in C:\Magento\Catalog\Controller\Adminhtml\Product\Builder.php:77' . PHP_EOL
            . 'Stack trace:' . PHP_EOL
            . '#0 [internal function]: Magento\Catalog\Controller\Adminhtml\Product\Builder->build(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#1 C:\Magento\Framework\Interception\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
            . '#2 C:\Magento\Framework\Interception\Chain\Chain.php(70): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
            . '#3 C:\Magento\Framework\Interception\Interceptor.php(136): Magento\Framework\Interception\Chain\Chain->invokeNext(\'Magento\\Catalog...\', \'build\', Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Array, \'configurable\')' . PHP_EOL
            . '#4 C:\Magento\ConfigurableProduct\Controller\Adminhtml\Product\Builder\Plugin.php(48): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->Magento\Framework\Interception\{closure}(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#5 [internal function]: Magento\ConfigurableProduct\Controller\Adminhtml\Product\Builder\Plugin->aroundBuild(Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Object(Closure), Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#6 {main} [] []' . PHP_EOL
            . '[' . $currentDate . ' 07:58:34] main.CRITICAL: exception \'Exception\' with message \'Exception from \Magento\Catalog\Controller\Adminhtml\Product\Builder::build\' in C:\Magento\Catalog\Controller\Adminhtml\Product\Builder.php:77' . PHP_EOL
            . 'Stack trace:' . PHP_EOL
            . '#0 [internal function]: Magento\Catalog\Controller\Adminhtml\Product\Builder->build(Object(Magento\Framework\App\Request\Http))' . PHP_EOL
            . '#1 C:\Magento\Framework\Interception\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
            . '#2 C:\Magento\Framework\Interception\Chain\Chain.php(70): Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
            . '#3 C:\Magento\Framework\Interception\Interceptor.php(136): Magento\Framework\Interception\Chain\Chain->invokeNext(\'Magento\\Catalog...\', \'build\', Object(Magento\Catalog\Controller\Adminhtml\Product\Builder\Interceptor), Array, \'configurable\')' . PHP_EOL
            . '#4 {main} [] []' . PHP_EOL
            ,
            '[2015-09-22 16:25:59] main.CRITICAL: Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
            . '[2015-09-22 16:25:59] main.CRITICAL: Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []' . PHP_EOL
            . '[2015-09-22 16:26:00] main.CRITICAL: Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []' . PHP_EOL
            . '[2015-09-22 16:26:01] main.CRITICAL: Invalid template file: \'\' [] []' . PHP_EOL
            . '[2015-09-22 16:26:09] main.CRITICAL: Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
            . '[2015-09-22 16:26:09] main.CRITICAL: Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []' . PHP_EOL
            . '[2015-09-22 16:26:09] main.CRITICAL: Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
            . '[' . $currentDate . '2 16:26:09] main.CRITICAL: Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []' . PHP_EOL
            . '[' . $currentDate . ' 16:26:12] main.CRITICAL: Invalid template file: \'\' [] []' . PHP_EOL
            . '[' . $currentDate . ' 16:26:27] main.CRITICAL: Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
            . '[' . $currentDate . ' 16:26:27] main.CRITICAL: Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
            . '[' . $currentDate . ' 16:26:27] main.CRITICAL: Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []' . PHP_EOL
            . '[' . $currentDate . ' 16:26:28] main.CRITICAL: Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
            . '[' . $currentDate . ' 16:26:30] main.CRITICAL: Invalid template file: \'\' [] []' . PHP_EOL
            . '[' . $currentDate . ' 16:26:33] main.CRITICAL: Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []' . PHP_EOL
        ];
        // @codingStandardsIgnoreEnd
        // @codingStandardsIgnoreStart
        $expectedData = [
            LogFilesData::LOG_FILES => [
                ['debug.log', $formattedSize, 12, $modifiedDate],
                ['exception.log', $formattedSize, 5, $modifiedDate],
                ['system.log', $formattedSize, 15, $modifiedDate]
            ],
            'system_messages' => [
                [
                    5,
                    'Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []',
                    $currentDate . ', 16:26:27',
                ],
                [
                    4,
                    'Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    $currentDate . ', 16:26:28',
                ],
                [
                    3,
                    'Invalid template file: \'\' [] []',
                    $currentDate . ', 16:26:30',
                ],
                [
                    3,
                    'Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    $currentDate . ', 16:26:33',
                ],
            ],
            'current_system_messages' => [
                [
                    2,
                    'Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    $currentDate . ', 16:26:28',
                ],
                [
                    2,
                    'Invalid template file: \'\' [] []',
                    $currentDate . ', 16:26:30',
                ],
                [
                    2,
                    'Broken reference: the \'page.breadcrumbs\' tries to reorder itself towards \'notifications\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    $currentDate . ', 16:26:33',
                ],
                [
                    1,
                    'Broken reference: the \'global.search\' tries to reorder itself towards \'notification.messages\', but their parents are different: \'header.inner.right\' and \'header\' respectively. [] []',
                    $currentDate . ', 16:26:27',
                ],
            ],
            'debug_messages' => [
                [
                    6,
                    'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    $currentDate . ', 14:18:33',
                ],
                [
                    2,
                    'Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    $currentDate . ', 14:12:22',
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/iphone-6s.html","invalidateInfo":{"identifier":"TARGET_RULE_2_1_1_0_0"},"is_exception":false} []',
                    '2015-09-22, 08:24:48',
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/admin/system_config/save/section/dev/store/1/","invalidateInfo":{"tags":["config_scopes","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    '2015-09-22, 09:28:07',
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/index/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    '2015-09-22, 10:14:34',
                ],
            ],
            'current_debug_messages' => [
                [
                    4,
                    'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    $currentDate . ', 14:18:33',
                ],
                [
                    2,
                    'Broken reference: the \'header\' tries to reorder itself towards \'global.notices\', but their parents are different: \'page.wrapper\' and \'notices.wrapper\' respectively. [] []',
                    $currentDate . ', 14:12:22',
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/support/report/create/?isAjax=true","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    $currentDate . ', 10:38:27',
                ],
            ],
            'exception_messages' => [
                [
                    4,
                    '\'Exception\' with message \'Exception from \\Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder::build\' in C:\\Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder.php:77',
                    '#0 [internal function]: Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder->build(Object(Magento\\Framework\\App\\Request\\Http))' . PHP_EOL
                    . '#1 C:\\Magento\\Framework\\Interception\\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
                    . '#2 C:\\Magento\\Framework\\Interception\\Chain\\Chain.php(70): Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder\\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
                    . '#3 C:\\Magento\\Framework\\Interception\\Interceptor.php(136): Magento\\Framework\\Interception\\Chain\\Chain->invokeNext(\'Magento\\Catalog...\', \'build\', Object(Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder\\Interceptor), Array, \'configurable\')' . PHP_EOL
                    . '',
                    $currentDate . ', 07:58:34',
                ],
                [
                    1,
                    '\'Magento\\Framework\\Exception\\LocalizedException\' with message \'Very bad exception.\' in C:\\Magento\\Support\\Model\\Report.php:144',
                    '#0 C:\\Magento\\Support\\Block\\Adminhtml\\Report\\View\\Tabs.php(69): Magento\\Support\\Model\\Report->prepareReportData()' . PHP_EOL
                    . '#1 C:\\Magento\\Framework\\View\\Element\\AbstractBlock.php(257): Magento\\Support\\Block\\Adminhtml\\Report\\View\\Tabs->_prepareLayout()' . PHP_EOL
                    . '#2 C:\\Magento\\Framework\\View\\Layout\\Generator\\Block.php(139): Magento\\Framework\\View\\Element\\AbstractBlock->setLayout(Object(Magento\\Framework\\View\\Layout\\Interceptor))' . PHP_EOL
                    . '#3 C:\\Magento\\Framework\\View\\Layout\\GeneratorPool.php(86): Magento\\Framework\\View\\Layout\\Generator\\Block->process(Object(Magento\\Framework\\View\\Layout\\Reader\\Context), Object(Magento\\Framework\\View\\Layout\\Generator\\Context))' . PHP_EOL
                    . '#4 C:\\Magento\\Framework\\View\\Layout.php(329): Magento\\Framework\\View\\Layout\\GeneratorPool->process(Object(Magento\\Framework\\View\\Layout\\Reader\\Context), Object(Magento\\Framework\\View\\Layout\\Generator\\Context))' . PHP_EOL
                    . '#5 C:\\Magento\\Framework\\View\\Layout\\Interceptor.php(89): Magento\\Framework\\View\\Layout->generateElements()' . PHP_EOL
                    . '',
                    '2015-09-17, 09:04:58',
                ],
            ],
            'current_exception_messages' => [
                [
                    3,
                    '\'Exception\' with message \'Exception from \\Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder::build\' in C:\\Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder.php:77',
                    '#0 [internal function]: Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder->build(Object(Magento\\Framework\\App\\Request\\Http))' . PHP_EOL
                    . '#1 C:\\Magento\\Framework\\Interception\\Interceptor.php(74): call_user_func_array(Array, Array)' . PHP_EOL
                    . '#2 C:\\Magento\\Framework\\Interception\\Chain\\Chain.php(70): Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder\\Interceptor->___callParent(\'build\', Array)' . PHP_EOL
                    . '#3 C:\\Magento\\Framework\\Interception\\Interceptor.php(136): Magento\\Framework\\Interception\\Chain\\Chain->invokeNext(\'Magento\\Catalog...\', \'build\', Object(Magento\\Catalog\\Controller\\Adminhtml\\Product\\Builder\\Interceptor), Array, \'configurable\')' . PHP_EOL
                    . '',
                    $currentDate . ', 07:58:34',
                ],
            ],
        ];
        // @codingStandardsIgnoreEnd
        $this->dateTimeMock->expects($this->atLeastOnce())->method('date')->willReturnMap($dateTimeMap);

        $this->directoryReadMock->expects($this->once())->method('read')->willReturn($logFiles);
        $this->directoryReadMock->expects($this->exactly(4))->method('isFile')->willReturn(true);
        $this->directoryReadMock->expects($this->exactly(4))->method('openFile')->willReturn($this->fileReadMock);
        $this->directoryReadMock->expects($this->exactly(3))->method('isReadable')->willReturn(true);

        $this->dataFormatterMock->expects($this->exactly(3))->method('formatBytes')->willReturn($formattedSize);

        $this->fileReadMock->expects($this->exactly(7))
            ->method('stat')
            ->willReturnOnConsecutiveCalls(
                $stat['debug.log'],
                $stat['debug.log'],
                $stat['exception.log'],
                $stat['exception.log'],
                $stat['empty.log'],
                $stat['system.log'],
                $stat['system.log']
            );
        $this->fileReadMock->expects($this->exactly(3))
            ->method('read')
            ->willReturnOnConsecutiveCalls($fileContent[0], $fileContent[1], $fileContent[2]);

        $this->assertEquals($expectedData, $this->logsFileData->getLogFilesData());
    }
}
