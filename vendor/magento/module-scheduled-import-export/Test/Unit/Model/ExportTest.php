<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScheduledImportExport\Test\Unit\Model;

use Magento\Framework\Filesystem;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Adapter\AbstractAdapter;
use Magento\ImportExport\Model\Export\Adapter\Factory as ExportAdapterFactory;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Export\Entity\Factory as ExportEntityFactory;
use Magento\ScheduledImportExport\Model\Export;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use Magento\ScheduledImportExport\Model\Scheduled\Operation as ScheduledOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ExportTest extends TestCase
{
    /**
     * @var ConfigInterface|MockObject
     */
    private $exportConfigMock;

    /**
     * @var ExportEntityFactory|MockObject
     */
    private $entityFactoryMock;

    /**
     * @var ExportAdapterFactory|MockObject
     */
    private $exportAdapterFactoryMock;

    /**
     * @var LocaleResolver|MockObject
     */
    private $localeResolver;

    /**
     * Enterprise data export model
     *
     * @var Export
     */
    private $model;

    /**
     * Date value for tests
     *
     * @var string
     */
    private $date = '2012-07-12';

    /**
     * Init model for future tests
     */
    protected function setUp(): void
    {
        $dateModelMock = $this->createPartialMock(DateTime::class, ['date']);
        $dateModelMock->expects(
            $this->any()
        )->method(
            'date'
        )->willReturnCallback(
            [$this, 'getDateCallback']
        );

        $loggerMock = $this->createMock(LoggerInterface::class);
        $filesystemMock = $this->createMock(Filesystem::class);
        $this->exportConfigMock = $this->createMock(ConfigInterface::class);
        $this->entityFactoryMock = $this->createMock(ExportEntityFactory::class);
        $this->exportAdapterFactoryMock = $this->createMock(ExportAdapterFactory::class);
        $this->localeResolver = $this->createMock(LocaleResolver::class);

        $this->model = new Export(
            $loggerMock,
            $filesystemMock,
            $this->exportConfigMock,
            $this->entityFactoryMock,
            $this->exportAdapterFactoryMock,
            $dateModelMock,
            $this->localeResolver,
            []
        );
    }

    /**
     * Test for method 'initialize'
     */
    public function testInitialize()
    {
        $operationData = [
            'file_info' => ['file_format' => 'csv'],
            'entity_attributes' => ['export_filter' => 'test', 'skip_attr' => 'test'],
            'entity_type' => 'customer',
            'operation_type' => 'export',
            'start_time' => '00:00:00',
            'id' => 1,
        ];
        $operation = $this->getOperationMock($operationData);
        $this->model->initialize($operation);

        foreach ($operationData as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $this->assertEquals($subValue, $this->model->getData($this->getMappedValue($subKey)));
                }
            } else {
                $this->assertEquals($value, $this->model->getData($this->getMappedValue($key)));
            }
        }
    }

    /**
     * Test for method 'getScheduledFileName'
     *
     * @param array $data
     * @param string $expectedFilename
     * @dataProvider entityTypeDataProvider
     */
    public function testGetScheduledFileName($data, $expectedFilename)
    {
        $data = array_merge(
            [
                'file_info' => ['file_format' => 'csv'],
                'entity_attributes' => ['export_filter' => 'test', 'skip_attr' => 'test'],
            ],
            $data
        );

        $operation = $this->getOperationMock($data);
        $this->model->initialize($operation);

        // we should set run date because initialize() resets $operation data
        if (!empty($data['run_date'])) {
            $this->model->setRunDate($data['run_date']);
        }

        $this->assertEquals($expectedFilename, $this->model->getScheduledFileName(), 'File name is wrong');
    }

    /**
     * Data provider for test 'testGetScheduledFileName'
     *
     * @return array
     */
    public function entityTypeDataProvider()
    {
        return [
            'Test file name when entity type provided' => [
                '$data' => ['entity_type' => 'customer', 'operation_type' => 'export'],
                '$expectedFilename' => $this->date . '_export_customer',
            ],
            'Test file name when entity subtype provided' => [
                '$data' => ['entity_type' => 'customer_address', 'operation_type' => 'export'],
                '$expectedFilename' => $this->date . '_export_customer_address',
            ],
            'Test file name when run date provided' => [
                '$data' => ['entity_type' => 'customer', 'operation_type' => 'export', 'run_date' => '11-11-11'],
                '$expectedFilename' => '11-11-11_export_customer',
            ]
        ];
    }

    public function testRunSchedule(): void
    {
        $this->localeResolver->expects($this->atLeastOnce())
            ->method('getLocale')
            ->willReturn('en_US');
        $this->localeResolver->expects($this->exactly(2))
            ->method('setLocale')
            ->with('en_US');

        $operationMock = $this->getMockBuilder(ScheduledOperation::class)
            ->disableOriginalConstructor()
            ->addMethods(['getFileInfo'])
            ->onlyMethods(['saveFileSource'])
            ->getMock();
        $fileInfo = [
            'file_format' => 'csv',
            'server_type' => 'file',
            'file_path' => 'var/export',
        ];
        $operationMock->expects($this->atLeastOnce())
            ->method('getFileInfo')
            ->willReturn($fileInfo);

        $data = [
            'entity' => 'entity_mock',
            'file_format' => 'file_format_mock',
            Export::FILTER_ELEMENT_GROUP => [],
        ];
        $entityModel = AbstractEntity::class;
        $fileFormatModel = AbstractAdapter::class;
        $this->exportConfigMock->expects($this->once())
            ->method('getEntities')
            ->willReturn(['entity_mock' => ['model' => $entityModel]]);
        $this->exportConfigMock->expects($this->once())
            ->method('getFileFormats')
            ->willReturn(['file_format_mock' => ['model' => $fileFormatModel]]);
        $entityAdapterMock = $this->createMock($entityModel);
        $entityAdapterMock->method('getEntityTypeCode')
            ->willReturn('entity_mock');
        $this->entityFactoryMock->expects($this->once())
            ->method('create')
            ->with($entityModel)
            ->willReturn($entityAdapterMock);
        $writerMock = $this->createMock(AbstractAdapter::class);
        $this->exportAdapterFactoryMock->expects($this->once())
            ->method('create')->willReturn($writerMock);
        $entityAdapterMock->expects($this->once())
            ->method('setWriter')
            ->with($writerMock)
            ->willReturnSelf();

        $result = "1,2,3\n";
        $entityAdapterMock->expects($this->once())
            ->method('export')
            ->willReturn($result);
        $operationMock->expects($this->once())
            ->method('saveFileSource')
            ->with($this->model, $result)
            ->willReturn(true);

        $this->model->setData($data);
        $this->model->runSchedule($operationMock);
    }

    /**
     * Retrieve data keys which used inside test model
     *
     * @param string $key
     * @return mixed
     */
    private function getMappedValue($key)
    {
        $modelDataMap = ['entity_type' => 'entity', 'start_time' => 'run_at', 'id' => 'scheduled_operation_id'];

        if (array_key_exists($key, $modelDataMap)) {
            return $modelDataMap[$key];
        }

        return $key;
    }

    /**
     * Retrieve operation mock
     *
     * @param array $operationData
     * @return Operation|MockObject
     */
    private function getOperationMock(array $operationData)
    {
        /** @var ScheduledOperation|MockObject $operation */
        $operation = $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getFileInfo', 'getEntityAttributes', 'getEntityType', 'getOperationType', 'getStartTime'])
            ->getMock();

        $operation->method('getId')->willReturn($operationData['id'] ?? null);
        $operation->method('getFileInfo')->willReturn($operationData['file_info'] ?? null);
        $operation->method('getEntityAttributes')->willReturn($operationData['entity_attributes'] ?? null);
        $operation->method('getEntityType')->willReturn($operationData['entity_type'] ?? null);
        $operation->method('getOperationType')->willReturn($operationData['operation_type'] ?? null);
        $operation->method('getStartTime')->willReturn($operationData['start_time'] ?? null);

        return $operation;
    }

    /**
     * Callback to use instead \Magento\Framework\Stdlib\DateTime\DateTime::date()
     *
     * @param string $format
     * @param int|string $input
     * @return string
     */
    public function getDateCallback($format, $input = null)
    {
        if (!empty($format) && $input !== null) {
            return $input;
        }

        return $this->date;
    }
}
