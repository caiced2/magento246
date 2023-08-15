<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\Export;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Block\Adminhtml\Report\Export\Html;
use Magento\Support\Model\Report;
use Magento\Support\Model\Report\Config;
use Magento\Support\Model\Report\DataConverter;
use Magento\Support\Model\Report\Group\Environment\EnvironmentSection;
use Magento\Support\Model\Report\Group\Environment\MysqlStatusSection;
use Magento\Support\Model\Report\Group\General\CacheStatusSection;
use Magento\Support\Model\Report\Group\General\DataCountSection;
use Magento\Support\Model\Report\Group\General\IndexStatusSection;
use Magento\Support\Model\Report\Group\General\VersionSection;
use Magento\Support\Model\Report\HtmlGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HtmlTest extends TestCase
{
    /**
     * @var Html
     */
    protected $exportHtmlBlock;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var Config|MockObject
     */
    protected $reportConfigMock;

    /**
     * @var DataConverter|MockObject
     */
    protected $dataConverterMock;

    /**
     * @var HtmlGenerator|MockObject
     */
    protected $htmlGeneratorMock;

    /**
     * @var ResolverInterface|MockObject
     */
    protected $localeResolverMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var Escaper|MockObject
     */
    protected $escaperMock;

    /**
     * @var Report|MockObject
     */
    protected $reportMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected $localeDateMock;

    protected function setUp(): void
    {
        $this->reportConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataConverterMock = $this->getMockBuilder(DataConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->htmlGeneratorMock = $this->getMockBuilder(HtmlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeResolverMock = $this->getMockBuilder(ResolverInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reportMock = $this->getMockBuilder(Report::class)
            ->setMethods(['getId', 'getClientHost', 'getCreatedAt', 'prepareReportData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeDateMock = $this->getMockForAbstractClass(TimezoneInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'logger' => $this->loggerMock,
                'escaper' => $this->escaperMock,
                'localeDate' => $this->localeDateMock
            ]
        );
        $this->exportHtmlBlock = $this->objectManagerHelper->getObject(
            Html::class,
            [
                'context' => $this->context,
                'reportConfig' => $this->reportConfigMock,
                'dataConverter' => $this->dataConverterMock,
                'htmlGenerator' => $this->htmlGeneratorMock,
                'localeResolver' => $this->localeResolverMock
            ]
        );

        $this->exportHtmlBlock->setData('report', $this->reportMock);
    }

    public function testGetDataConverter()
    {
        $this->assertSame($this->dataConverterMock, $this->exportHtmlBlock->getDataConverter());
    }

    public function testGetHtmlGenerator()
    {
        $this->assertSame($this->htmlGeneratorMock, $this->exportHtmlBlock->getHtmlGenerator());
    }

    public function testGetReport()
    {
        $this->assertSame($this->reportMock, $this->exportHtmlBlock->getReport());
    }

    public function testGetReportsNoId()
    {
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals([], $this->exportHtmlBlock->getReports());
    }

    public function testGetReportsEmptyData()
    {
        $reportGroups = [
            'general' => [
                'title' => __('General'),
                'sections' => [
                    40 => VersionSection::class,
                    50 => DataCountSection::class,
                    70 => CacheStatusSection::class,
                    80 => IndexStatusSection::class
                ],
                'priority' => 10
            ]
        ];

        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->reportMock->expects($this->once())
            ->method('prepareReportData')
            ->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(__('Requested system report has no data to output.'))
            ->willReturn(true);
        $this->reportConfigMock->expects($this->any())
            ->method('getGroups')
            ->willReturn($reportGroups);

        $this->assertEquals([], $this->exportHtmlBlock->getReports());
    }

    /**
     * @param array $reportData
     * @param array $reportGroups
     * @param array $expectedResult
     *
     * @dataProvider getReportsDataProvider
     */
    public function testGetReports(
        array $reportData,
        array $reportGroups,
        array $expectedResult
    ) {
        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->reportMock->expects($this->any())
            ->method('prepareReportData')
            ->willReturn($reportData);
        $this->loggerMock->expects($this->never())
            ->method('error');
        $this->reportConfigMock->expects($this->any())
            ->method('getGroups')
            ->willReturn($reportGroups);

        $this->assertEquals($expectedResult, $this->exportHtmlBlock->getReports());
    }

    /**
     * @return array
     */
    public function getReportsDataProvider()
    {
        return [
            [
                'reportData' => [VersionSection::class => [
                    'Magento Version' => []
                ], DataCountSection::class => [
                    'Data Count' => []
                ], CacheStatusSection::class => [
                    'Cache Status' => []
                ], IndexStatusSection::class => [
                    'Index Status' => []
                ], EnvironmentSection::class => [
                    'Environment Information' => []
                ], MysqlStatusSection::class => [
                    'MySQL Status' => []
                ]
                ],
                'reportGroups' => [
                    'general' => [
                        'title' => __('General'),
                        'sections' => [
                            40 => VersionSection::class,
                            50 => DataCountSection::class,
                            70 => CacheStatusSection::class,
                            80 => IndexStatusSection::class
                        ],
                        'priority' => 10
                    ],
                    'environment' => [
                        'title' => __('Environment'),
                        'sections' => [
                            410 => EnvironmentSection::class,
                            420 => MysqlStatusSection::class
                        ],
                        'priority' => 30
                    ]
                ],
                'expectedResult' => [
                    'general' => [
                        'title' => __('General'),
                        'reports' => [
                            'Magento Version' => [],
                            'Data Count' => [],
                            'Cache Status' => [],
                            'Index Status' => []
                        ]
                    ],
                    'environment' => [
                        'title' => __('Environment'),
                        'reports' => [
                            'Environment Information' => [],
                            'MySQL Status' => []
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testGetReportTitleNoId()
    {
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->reportMock->expects($this->never())
            ->method('getClientHost');

        $this->assertEquals('', $this->exportHtmlBlock->getReportTitle());
    }

    public function testGetReportTitle()
    {
        $clientHost = 'client.host';

        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->reportMock->expects($this->any())
            ->method('getClientHost')
            ->willReturn($clientHost);

        $this->assertEquals($clientHost, $this->exportHtmlBlock->getReportTitle());
    }

    public function testGetReportCreationDateNoId()
    {
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->reportMock->expects($this->never())
            ->method('getCreatedAt');

        $this->assertEquals('', $this->exportHtmlBlock->getReportCreationDate());
    }

    public function testGetReportCreationDate()
    {
        $date = '2020-03-04 12:00:00';
        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->localeDateMock->expects($this->any())
            ->method('formatDateTime')
            ->willreturn($date);

        $this->assertEquals($date, $this->exportHtmlBlock->getReportCreationDate());
    }

    public function testGetCopyrightTextNoId()
    {
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals('', $this->exportHtmlBlock->getCopyrightText());
    }

    public function testGetCopyrightText()
    {
        $expectedResult = __('&copy; Magento Commerce Inc., %1', date('Y'));

        $this->reportMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->assertEquals($expectedResult, $this->exportHtmlBlock->getCopyrightText());
    }

    public function testGetLangDataIsSet()
    {
        $lang = 'en';

        $this->exportHtmlBlock->setData('lang', $lang);

        $this->localeResolverMock->expects($this->never())
            ->method('getLocale');

        $this->assertEquals($lang, $this->exportHtmlBlock->getLang());
    }

    public function testGetLang()
    {
        $lang = 'en';
        $locale = 'english';

        $this->localeResolverMock->expects($this->once())
            ->method('getLocale')
            ->willReturn($locale);

        $this->assertEquals($lang, $this->exportHtmlBlock->getLang());
    }
}
