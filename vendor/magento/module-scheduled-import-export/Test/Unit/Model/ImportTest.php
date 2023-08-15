<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Test class for \Magento\ScheduledImportExport\Model\Import
 */
namespace Magento\ScheduledImportExport\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\FileTransferFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Helper\Data;
use Magento\ImportExport\Model\Export\Adapter\CsvFactory;
use Magento\ImportExport\Model\History;
use Magento\ImportExport\Model\Import\ConfigInterface;
use Magento\ImportExport\Model\Import\Entity\Factory;
use Magento\ImportExport\Model\Source\Upload;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\ScheduledImportExport\Model\Import;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregator;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\ImportExport\Model\Source\Import\Behavior\Factory as BehaviorFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportTest extends TestCase
{
    /**
     * Enterprise data import model
     *
     * @var Import
     */
    private $_model;

    /**
     * @var ConfigInterface|MockObject
     */
    private $importConfigMock;

    /**
     * @var ImportData|MockObject
     */
    private $importData;

    /**
     * @var Factory|MockObject
     */
    private $entityFactory;

    /**
     * @var Upload|MockObject
     */
    private $upload;

    /**
     * Init model for future tests
     */
    protected function setUp(): void
    {
        $this->importConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->onlyMethods(['getEntities'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entityFactory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->importData = $this->getMockBuilder(ImportData::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBehavior', 'getEntityTypeCode', 'markProcessedBunches'])
            ->getMock();
        $filesystemWrite = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->addMethods(['close', 'readCsv', 'seek'])
            ->getMock();
        $filesystemWrite->expects($this->any())
            ->method('readCsv')
            ->willReturn([1,2]);
        $this->upload = $this->createMock(Upload::class);
        $directory = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['openFile', 'getRelativePath', 'readFile', 'getDriver', 'getAbsolutePath'])
            ->getMock();
        $directory->method('getRelativePath')
            ->willReturn('');
        $directory->method('getAbsolutePath')
            ->willReturn('');
        $directory->method('readFile')
            ->willReturn(false);
        $directory->expects($this->any())
            ->method('openFile')
            ->willReturn($filesystemWrite);
        $driver = $this->getMockBuilder(Filesystem\DriverInterface::class)
            ->onlyMethods(['fileGetContents'])
            ->getMockForAbstractClass();
        $driver->method('fileGetContents')->willReturn('test string');
        $directory->expects($this->any())
            ->method('getDriver')
            ->willReturn($driver);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')
            ->with($this->equalTo(DirectoryList::VAR_IMPORT_EXPORT))
            ->willReturn($directory);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $indexerRegistry = $this->createMock(IndexerRegistry::class);
        $this->_model = new Import(
            $logger,
            $filesystem,
            $this->createMock(Data::class),
            $this->createMock(ScopeConfigInterface::class),
            $this->importConfigMock,
            $this->entityFactory,
            $this->importData,
            $this->createMock(CsvFactory::class),
            $this->createMock(FileTransferFactory::class),
            $this->getMockBuilder(UploaderFactory::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create'])
                ->getMock(),
            $this->createMock(BehaviorFactory::class),
            $indexerRegistry,
            $this->createMock(History::class),
            $this->createMock(DateTime::class),
            [],
            null,
            null,
            $this->upload
        );
    }

    /**
     * Test for method 'initialize'
     *
     * @return void
     */
    public function testInitialize(): void
    {
        /**
         * @var Operation $operation
         */
        $operation = $this->getMockBuilder(Operation::class)
            ->addMethods(['getFileInfo', 'getEntityType', 'getBehavior', 'getOperationType', 'getStartTime'])
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $fileInfo = [
            'entity_type' => 'another customer',
            'behavior' => 'replace',
            'operation_type' => 'import',
            'custom_option' => 'value',
        ];
        $operationData = [
            'entity' => 'test entity',
            'behavior' => 'customer',
            'operation_type' => 'update',
            'run_at' => '00:00:00',
            'scheduled_operation_id' => 1,
        ];

        $operation->expects($this->once())->method('getFileInfo')->willReturn($fileInfo);
        $operation->expects($this->once())->method('getEntityType')->willReturn($operationData['entity']);
        $operation->expects($this->once())->method('getBehavior')->willReturn($operationData['behavior']);
        $operation->expects($this->once())->method('getOperationType')->willReturn($operationData['operation_type']);
        $operation->expects($this->once())->method('getStartTime')->willReturn($operationData['run_at']);
        $operation->expects($this->once())->method('getId')->willReturn($operationData['scheduled_operation_id']);

        $importMock = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData'])
            ->getMock();
        $expectedData = array_merge($fileInfo, $operationData);
        $importMock->expects($this->once())->method('setData')->with($expectedData);

        $actualResult = $importMock->initialize($operation);
        $this->assertEquals($importMock, $actualResult);
    }

    /**
     * Test for method 'runSchedule'
     *
     * @return void
     */
    public function testRunSchedule(): void
    {
        $fileSource = '\file\path.csv';
        $entity = 'entity_product';
        /**
         * @var $operation Operation
         */
        $operation = $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFileSource'])
            ->addMethods(['getForceImport', 'getEntityType'])
            ->getMock();
        $operation->expects($this->any())->method('getFileSource')->willReturn($fileSource);
        $operation->expects($this->any())->method('getForceImport')->willReturn(true);
        $operation->expects($this->any())->method('getEntityType')->willReturn($entity);
        $errorAggregator = $this->getMockBuilder(ProcessingErrorAggregator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initValidationStrategy'])
            ->addMethods([ 'clean'])
            ->getMock();
        $errorAggregator->expects($this->any())->method('clean')->willReturn($fileSource);
        $importEntity =  $this->getMockBuilder(\Magento\ImportExport\Model\Import\Entity\AbstractEntity::class)
            ->onlyMethods(
                [
                    'setSource',
                    'validateData',
                    'getEntityTypeCode',
                    'isNeedToLogInHistory',
                    'getErrorAggregator',
                    'importData',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entityFactory->expects($this->any())->method('create')->willReturn($importEntity);
        $this->importConfigMock->expects($this->any())->method('getEntities')->willReturn(
            [$entity => ['model' => $importEntity]]
        );
        $importEntity->expects($this->any())->method('getEntityTypeCode')->willReturn($entity);
        $importEntity->expects($this->any())->method('isNeedToLogInHistory')->willReturn(false);
        $importEntity->expects($this->any())->method('getErrorAggregator')->willReturn($errorAggregator);
        $importEntity->expects($this->any())->method('setSource')->willReturnSelf();
        $importEntity->expects($this->any())->method('importData')->willReturn(true);
        $importEntity->expects($this->any())->method('validateData')->willReturn($errorAggregator);
        $this->importData->expects($this->any())->method('getBehavior')->willReturn(
            \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE
        );
        $this->importData->expects($this->any())->method('getEntityTypeCode')->willReturn($entity);
        $this->_model->setData('entity', $entity);
        $this->assertTrue($this->_model->runSchedule($operation));
    }
}
