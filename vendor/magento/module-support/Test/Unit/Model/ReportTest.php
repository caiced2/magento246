<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model;

use Magento\Enterprise\Model\ProductMetadata;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report;
use Magento\Support\Model\Report\Config;
use Magento\Support\Model\Report\DataConverter;
use Magento\Support\Model\Report\Group\AbstractSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportTest extends TestCase
{
    /**
     * @var Report
     */
    protected $report;

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
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var DataConverter|MockObject
     */
    protected $dataConverterMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var AbstractSection|MockObject
     */
    protected $sectionMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected $timeZoneMock;

    /**
     * @var ProductMetadata|MockObject
     */
    protected $productMetadataMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory|MockObject
     */
    protected $dateFactoryMock;

    /**
     * @var DateTime|MockObject
     */
    private $dateTimeMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->reportConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMockForAbstractClass();
        $this->dataConverterMock = $this->getMockBuilder(DataConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->sectionMock = $this->getMockBuilder(AbstractSection::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->timeZoneMock = $this->getMockForAbstractClass(TimezoneInterface::class);
        $this->productMetadataMock = $this->getMockBuilder(ProductMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateFactoryMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->dateTimeMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'logger' => $this->loggerMock
            ]
        );
        $this->report = $this->objectManagerHelper->getObject(
            Report::class,
            [
                'context' => $this->context,
                'reportConfig' => $this->reportConfigMock,
                'objectManager' => $this->objectManagerMock,
                'dataConverter' => $this->dataConverterMock,
                'timeZone' => $this->timeZoneMock,
                'dateFactory' => $this->dateFactoryMock,
                'productMetadata' => $this->productMetadataMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGenerate(): void
    {
        $groups = ['some', 'groups', 'go', 'here'];
        $sections = ['some', 'sections', 'go', 'here'];
        $reportData = ['some' => [], 'sections' => [], 'go' => [], 'here' => []];

        $this->reportConfigMock->expects($this->once())
            ->method('getSectionNamesByGroup')
            ->with($groups)
            ->willReturn($sections);
        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sectionMock);
        $this->sectionMock->expects($this->any())
            ->method('generate')
            ->willReturn([]);

        $this->report->generate($groups);

        $this->assertEquals($groups, $this->report->getReportGroups());
        $this->assertEquals($reportData, $this->report->getReportData());
    }

    /**
     * @return void
     */
    public function testPrepareReportDataNoData(): void
    {
        $this->assertFalse($this->report->prepareReportData());
    }

    /**
     * @return void
     */
    public function testPrepareReportData(): void
    {
        $errorMessage = 'Something gone wrong';
        $exception = new LocalizedException(__($errorMessage));

        $reportData = [
            'section1' => [
                'title1' => [
                    'headers' => ['header1'],
                    'data' => ['data1']
                ]
            ],
            'section2' => [],
            'section3' => [
                'title3' => [
                    'data' => ['exception']
                ]
            ]
        ];

        $preparedData = [
            'section1' => [
                'title1' => [
                    'headers' => ['header1'],
                    'data' => ['data1']
                ]
            ],
            'section3' => [
                'title3' => [
                    'error' => $errorMessage
                ]
            ]
        ];

        $this->dataConverterMock
            ->method('prepareData')
            ->withConsecutive(
                [['headers' => ['header1'], 'data' => ['data1']]],
                [['data' => ['exception']]]
            )
            ->willReturnOnConsecutiveCalls(
                ['headers' => ['header1'], 'data' => ['data1']],
                $this->throwException($exception)
            );
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception, [])
            ->willReturn(true);

        $this->report->setReportData($reportData);
        $this->assertEquals($preparedData, $this->report->prepareReportData());
    }

    /**
     * @return void
     */
    public function testGetFileNameForReportDownloadNoId(): void
    {
        $this->assertEquals('', $this->report->getFileNameForReportDownload());
    }

    /**
     * @return void
     */
    public function testGetFileNameForReportDownload(): void
    {
        $date = '2015-12-03-23-45-11';
        $this->report->setId(3);
        $this->report->setClientHost('/local/host');
        $this->timeZoneMock->expects($this->once())->method('formatDateTime')->willReturn($date);

        $this->assertEquals(
            'report-2015-12-03-23-45-11_localhost.html',
            $this->report->getFileNameForReportDownload()
        );
    }

    /**
     * @return void
     */
    public function testBeforeSave(): void
    {
        $testMagentoVersion = '0.0.0';
        $this->productMetadataMock->expects($this->once())->method('getVersion')->willReturn($testMagentoVersion);
        $this->report->beforeSave();
        $this->assertEquals($testMagentoVersion, $this->report->getMagentoVersion());
    }
}
