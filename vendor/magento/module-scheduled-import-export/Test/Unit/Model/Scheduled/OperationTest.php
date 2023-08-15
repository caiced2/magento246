<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScheduledImportExport\Test\Unit\Model\Scheduled;

use Magento\Framework\App\Config;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\Io\Ftp;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregator;
use Magento\ScheduledImportExport\Model\Export;
use Magento\ScheduledImportExport\Model\Import;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use Magento\ScheduledImportExport\Model\Scheduled\Operation\Data;
use Magento\ScheduledImportExport\Model\Scheduled\Operation\DataFactory;
use Magento\ScheduledImportExport\Model\Scheduled\Operation\GenericFactory;
use Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\MockObject as Mock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class OperationTest extends TestCase
{
    public const DATE = '2014/01/01';

    /**
     * Default date value
     *
     * @var string
     */
    protected $_date = '00-00-00';

    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\Operation
     */
    protected $model;

    /**
     * @var Context|Mock
     */
    protected $contextMock;

    /**
     * @var Registry|Mock
     */
    protected $registryMock;

    /**
     * @var \Magento\Framework\Filesystem|Mock
     */
    protected $filesystemMock;

    /**
     * @var StoreManager|Mock
     */
    protected $storeManagerMock;

    /**
     * @var GenericFactory|Mock
     */
    protected $genericFactoryMock;

    /**
     * @var DataFactory|Mock
     */
    protected $dataFactoryMock;

    /**
     * @var ValueFactory|Mock
     */
    protected $valueFactoryMock;

    /**
     * @var DateTime|Mock
     */
    protected $datetimeMock;

    /**
     * @var Config|Mock
     */
    protected $configScopeMock;

    /**
     * @var StringUtils|Mock
     */
    protected $stringStdLibMock;

    /**
     * @var TransportBuilder|Mock
     */
    protected $transportBuilderMock;

    /**
     * @var Ftp|Mock
     */
    protected $ftpMock;

    /**
     * @var \Magento\ScheduledImportExport\Model\ResourceModel\Scheduled\Operation|Mock
     */
    protected $resourceMock;

    /**
     * @var AbstractDb|Mock
     */
    protected $resourceCollectionMock;

    /**
     * @var LoggerInterface|Mock
     */
    protected $loggerInterfaceMock;

    /**
     * @var Operation\OperationInterface|Mock
     */
    private $operationInterfaceMock;

    /**
     * @var ProcessingErrorAggregator|Mock
     */
    private $errorAggregatorMock;

    /**
     * @var WriteInterface|Mock
     */
    private $writeInterfaceMock;

    /**
     * @var Store|Mock
     */
    private $storeMock;

    /**
     * @var TransportInterface|Mock
     */
    private $transportInterfaceMock;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->onlyMethods(['getLogger'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getLogger')
            ->willReturn($this->loggerInterfaceMock);

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->onlyMethods(['getDirectoryWrite', 'getDirectoryRead'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManager::class)
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $genericClass = GenericFactory::class;
        $this->genericFactoryMock = $this->getMockBuilder($genericClass)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataClass = DataFactory::class;
        $this->dataFactoryMock = $this->getMockBuilder($dataClass)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueFactoryMock = $this->getMockBuilder(ValueFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->datetimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configScopeMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stringStdLibMock = $this->getMockBuilder(StringUtils::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportBuilderMock = $this->getMockBuilder(TransportBuilder::class)
            ->onlyMethods(
                [
                    'setTemplateIdentifier',
                    'setTemplateOptions',
                    'setTemplateVars',
                    'setFrom',
                    'addTo',
                    'getTransport'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->ftpMock = $this->getMockBuilder(Ftp::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock =
            $this->getMockBuilder(\Magento\ScheduledImportExport\Model\ResourceModel\Scheduled\Operation::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->resourceCollectionMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $data = [];
        $serializer = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->operationInterfaceMock = $this->getMockBuilder(OperationInterface::class)
            ->onlyMethods(['runSchedule', 'initialize', 'addLogComment', 'getFormatedLogTrace'])
            ->addMethods(['getInstance', 'setRunDate', 'getErrorAggregator'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->errorAggregatorMock = $this->getMockBuilder(ProcessingErrorAggregator::class)
            ->onlyMethods(['getErrorsCount'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->writeInterfaceMock = $this->getMockBuilder(WriteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportInterfaceMock = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->model = new Operation(
            $this->contextMock,
            $this->registryMock,
            $this->filesystemMock,
            $this->storeManagerMock,
            $this->genericFactoryMock,
            $this->dataFactoryMock,
            $this->valueFactoryMock,
            $this->datetimeMock,
            $this->configScopeMock,
            $this->stringStdLibMock,
            $this->transportBuilderMock,
            $this->ftpMock,
            $this->resourceMock,
            $this->resourceCollectionMock,
            $data,
            $serializer
        );
    }

    /**
     * @param array $fileInfo
     * @param int|string $lastRunDate
     * @param string $expectedPath
     *
     * @return void
     * @dataProvider getHistoryFilePathDataProvider
     */
    public function testGetHistoryFilePath(array $fileInfo, $lastRunDate, string $expectedPath): void
    {
        $model = $this->_getScheduledOperationModel($fileInfo);

        $model->setLastRunDate($lastRunDate);

        $this->assertEquals($expectedPath, $model->getHistoryFilePath());
    }

    /**
     * @return array
     */
    public function getHistoryFilePathDataProvider(): array
    {
        $dir = Operation::LOG_DIRECTORY . Operation::FILE_HISTORY_DIRECTORY . self::DATE . '/';
        return [
            'empty file name' => [
                '$fileInfo' => ['file_format' => 'csv', 'extension' => ''],
                '$lastRunDate' => null,
                '$expectedPath' => $dir . $this->_date . '_export_catalog_product.csv'
            ],
            'filled file name' => [
                '$fileInfo' => ['file_name' => 'test.xls', 'extension' => 'xls'],
                '$lastRunDate' => null,
                '$expectedPath' => $dir . $this->_date . '_export_catalog_product.xls'
            ],
            'set last run date' => [
                '$fileInfo' => ['file_name' => 'test.xls', 'extension' => 'xls'],
                '$lastRunDate' => '11-11-11',
                '$expectedPath' => $dir . '11-11-11_export_catalog_product.xls'
            ]
        ];
    }

    /**
     * Get mocked model.
     *
     * @param array $fileInfo
     *
     * @return Operation|MockObject
     */
    protected function _getScheduledOperationModel(array $fileInfo): Operation
    {
        $objectManagerHelper = new ObjectManager($this);

        $dateModelMock = $this->createPartialMock(DateTime::class, ['date']);
        $dateModelMock->expects(
            $this->any()
        )->method(
            'date'
        )->willReturnCallback(
            [$this, 'getDateCallback']
        );

        //TODO Get rid of mocking methods from testing model when this model will be re-factored

        $operationFactory = $this->createPartialMock(
            DataFactory::class,
            ['create']
        );

        $directory = $this->getMockBuilder(
            Write::class
        )->disableOriginalConstructor()
            ->getMock();
        $directory->expects($this->once())->method('getAbsolutePath')->willReturnArgument(0);
        $filesystem =
            $this->getMockBuilder(Filesystem::class)
                ->disableOriginalConstructor()
                ->getMock();
        $filesystem->expects($this->once())->method('getDirectoryWrite')->willReturn($directory);

        $file = $this->createPartialMock(
            File::class,
            ['getPathInfo']
        );
        $file->expects($fileInfo['extension'] ? $this->once() : $this->never())
            ->method('getPathInfo')
            ->willReturn(['extension' => $fileInfo['extension']]);

        $params = [
            'operationFactory' => $operationFactory,
            'filesystem' => $filesystem,
        ];
        $arguments = $objectManagerHelper->getConstructArguments(
            Operation::class,
            $params
        );
        $arguments['dateModel'] = $dateModelMock;
        $arguments['file'] = $file;
        $model = $this->getMockBuilder(Operation::class)
            ->onlyMethods(['_init'])
            ->addMethods(['getOperationType', 'getEntityType', 'getFileInfo'])
            ->setConstructorArgs($arguments)
            ->getMock();

        $model->expects($this->once())->method('getOperationType')->willReturn('export');
        $model->expects($this->once())->method('getEntityType')->willReturn('catalog_product');
        $model->expects($this->once())->method('getFileInfo')->willReturn($fileInfo);

        return $model;
    }

    /**
     * Callback to use instead of \Magento\Framework\Stdlib\DateTime\DateTime::date()
     *
     * @param string $format
     * @param int|string $input
     *
     * @return string
     */
    public function getDateCallback(string $format, $input = null): string
    {
        if (!empty($format) && $input !== null) {
            return $input;
        }
        if ($format === 'Y/m/d') {
            return self::DATE;
        }
        return $this->_date;
    }

    /**
     * Test saveFileSource() with all valid parameters.
     *
     * @return void
     */
    public function testSaveFileSourceFtp(): void
    {
        $fileContent = 'data to export';
        $fileInfo = [
            'file_name' => 'somefile.csv',
            'file_format' => 'csv',
            'file_path' => '/test',
            'server_type' => Data::FTP_STORAGE,
        ];
        $datetime = '1970-01-01';
        $operationType = 'export';
        $entityType = 'product';
        $resultFile = '1970-01-01_export_product.csv';
        $scheduledFileName = 'scheduled_filename';
        $serverOptions = $this->getSourceOptions();
        $openArguments = ['path' => $fileInfo['file_path']];
        $writeFilePath = $fileInfo['file_path'] . '/' . $scheduledFileName . '.' . $fileInfo['file_format'];
        $writeResult = true;

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->willReturn($datetime);

        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($dataMock);
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->willReturn($serverOptions);

        $exportMock = $this->getMockBuilder(Export::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exportMock
            ->method('addLogComment');
        $exportMock->expects($this->any())
            ->method('getScheduledFileName')
            ->willReturn($scheduledFileName);

        $writeDirectoryMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($resultFile);
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturn($writeDirectoryMock);

        $this->ftpMock->expects($this->once())
            ->method('open')
            ->with($openArguments);
        $this->ftpMock->expects($this->once())
            ->method('write')
            ->with($writeFilePath, $fileContent)
            ->willReturn($writeResult);

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->saveFileSource($exportMock, $fileContent);
        $this->assertTrue($result);
    }

    /**
     * Test saveFileSource() through Filesystem library.
     *
     * @return void
     */
    public function testSaveFileSourceFile(): void
    {
        $fileContent = 'data to export';
        $fileInfo = [
            'file_name' => 'somefile.csv',
            'file_format' => 'csv',
            'file_path' => '/test',
            'server_type' => Data::FILE_STORAGE,
        ];
        $datetime = '1970-01-01';
        $operationType = 'export';
        $entityType = 'product';
        $resultFile = '1970-01-01_export_product.csv';
        $scheduledFileName = 'scheduled_filename';
        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->willReturn($datetime);

        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($dataMock);
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->willReturn($serverOptions);

        $exportMock = $this->getMockBuilder(Export::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exportMock
            ->method('addLogComment');
        $exportMock->expects($this->any())
            ->method('getScheduledFileName')
            ->willReturn($scheduledFileName);

        $writeDirectoryMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($resultFile);
        $writeDirectoryMock->expects($this->any())
            ->method('writeFile')
            ->willReturn(true);
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturn($writeDirectoryMock);

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->saveFileSource($exportMock, $fileContent);
        $this->assertTrue($result);
    }

    /**
     * Test saveFileSource() that throws Exception during opening ftp connection.
     *
     * @return void
     */
    public function testSaveFileSourceException(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage(
            'We couldn\'t write file "scheduled_filename.csv" to "/test" with the "ftp" driver.'
        );
        $fileContent = 'data to export';
        $fileInfo = [
            'file_name' => 'somefile.csv',
            'file_format' => 'csv',
            'file_path' => '/test',
            'server_type' => Data::FTP_STORAGE,
        ];
        $datetime = '1970-01-01';
        $operationType = 'export';
        $entityType = 'product';
        $resultFile = '1970-01-01_export_product.csv';
        $scheduledFileName = 'scheduled_filename';
        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->willReturn($datetime);

        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($dataMock);
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->willReturn($serverOptions);

        $exportMock = $this->getMockBuilder(Export::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exportMock
            ->method('addLogComment');
        $exportMock->expects($this->any())
            ->method('getScheduledFileName')
            ->willReturn($scheduledFileName);

        $writeDirectoryMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn($resultFile);
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturn($writeDirectoryMock);

        $this->ftpMock->expects($this->once())
            ->method('open')
            ->willThrowException(new \Exception('Can not open file'));

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->saveFileSource($exportMock, $fileContent);
        $this->assertNull($result);
    }

    /**
     * Test getFileSource() if 'file_name' not exists.
     *
     * @return void
     */
    public function testGetFileSource(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('We can\'t read the file source because the file name is empty.');
        $fileInfo = [];
        $importMock = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model->setFileInfo($fileInfo);
        $result = $this->model->getFileSource($importMock);
        $this->assertNull($result);
    }

    /**
     * Test getFileSource() import data by using ftp.
     *
     * @return void
     */
    public function testGetFileSourceFtp(): void
    {
        $datetime = '1970-01-01';
        $operationType = 'import';
        $entityType = 'product';
        $fileInfo = [
            'file_name' => 'source.csv',
            'file_path' => '/test',
            'server_type' => Data::FTP_STORAGE,
        ];
        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->willReturn($datetime);

        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($dataMock);
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->willReturn($serverOptions);

        $writeDirectoryMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturnArgument(0);
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturn($writeDirectoryMock);

        $importMock = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->getMock();
        $importMock->expects($this->any())
            ->method('addLogComment');

        $this->ftpMock->expects($this->any())
            ->method('open');
        $this->ftpMock->expects($this->any())
            ->method('read')
            ->willReturn(true);

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->getFileSource($importMock);
        $this->assertEquals('csv', pathinfo($result, PATHINFO_EXTENSION));
    }

    /**
     * Test getFileSource() import data by using Filesystem.
     *
     * @return void
     */
    public function testGetFileSourceFile(): void
    {
        $datetime = '1970-01-01';
        $operationType = 'import';
        $entityType = 'product';
        $fileInfo = [
            'file_name' => 'source.csv',
            'file_path' => 'test',
            'server_type' => Data::FILE_STORAGE,
        ];
        $source = trim($fileInfo['file_path'] . '/' . $fileInfo['file_name'], '\\/');
        $contents = 'test content';

        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->willReturn($datetime);

        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($dataMock);
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->willReturn($serverOptions);

        $writeDirectoryMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $readDirectoryMock = $this->getMockBuilder(Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $readDirectoryMock->expects($this->any())
            ->method('getRelativePath')
            ->willReturnArgument(0);
        $readDirectoryMock->expects($this->once())
            ->method('isExist')
            ->with($source)
            ->willReturn(true);
        $readDirectoryMock->expects($this->once())
            ->method('readFile')
            ->with($source)
            ->willReturn($contents);
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturnArgument(0);
        $writeDirectoryMock->expects($this->any())
            ->method('writeFile')
            ->willReturn(true);
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturn($writeDirectoryMock);
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryRead')
            ->willReturn($readDirectoryMock);

        $importMock = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->getMock();
        $importMock->expects($this->any())
            ->method('addLogComment');

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->getFileSource($importMock);
        $this->assertEquals('csv', pathinfo($result, PATHINFO_EXTENSION));
    }

    /**
     * @return void
     */
    public function testGetFileSourceFtpException(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('We can\'t read the import file.');
        $datetime = '1970-01-01';
        $operationType = 'import';
        $entityType = 'product';
        $fileInfo = [
            'file_name' => 'source.csv',
            'file_path' => '/test',
            'server_type' => Data::FTP_STORAGE
        ];
        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->willReturn($datetime);

        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($dataMock);
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->willReturn($serverOptions);

        $writeDirectoryMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturnArgument(0);
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturn($writeDirectoryMock);

        $importMock = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->getMock();
        $importMock->expects($this->any())
            ->method('addLogComment');

        $this->ftpMock->expects($this->any())
            ->method('open')
            ->willThrowException(new FileSystemException(__('Can not open file')));

        $this->ftpMock->expects($this->any())
            ->method('read')
            ->willReturn(true);

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->getFileSource($importMock);
        $this->assertNull($result);
    }

    /**
     * @param array $fileInfo
     * @param string $operationType
     * @param string $entityType
     *
     * @return void
     */
    protected function setModelData(array $fileInfo, string $operationType, string $entityType): void
    {
        $this->model->setFileInfo($fileInfo);
        $this->model->setOperationType($operationType);
        $this->model->setEntityType($entityType);
    }

    /**
     * @return array
     */
    protected function getSourceOptions(): array
    {
        return [
            Data::FTP_STORAGE => 'ftp',
            Data::FILE_STORAGE => 'file'
        ];
    }

    /**
     * @return void
     */
    public function testRun(): void
    {
        $operationType = 'import';
        $entityType = 'product';
        $fileInfo = [
            'file_name' => 'source.csv',
            'file_path' => '/test',
            'server_type' => Data::FTP_STORAGE
        ];
        $this->setModelData($fileInfo, $operationType, $entityType);

        $this->operationInterfaceMock->expects($this->any())
            ->method('getInstance')->willReturnSelf();
        $this->operationInterfaceMock->expects($this->any())
            ->method('setRunDate')->willReturnSelf();
        $this->operationInterfaceMock->expects($this->once())
            ->method('runSchedule')
            ->willReturn(false);
        $this->operationInterfaceMock->method('getFormatedLogTrace')
            ->willReturn('');
        $this->operationInterfaceMock->expects($this->atLeastOnce())
            ->method('getErrorAggregator')
            ->willReturn($this->errorAggregatorMock);
        $this->errorAggregatorMock->expects($this->once())
            ->method('getErrorsCount')
            ->willReturn(0);
        $this->genericFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->operationInterfaceMock);
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->willReturn($this->writeInterfaceMock);
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->transportBuilderMock->expects($this->any())
            ->method('setTemplateIdentifier')->willReturnSelf();
        $this->transportBuilderMock->expects($this->any())
            ->method('setTemplateOptions')->willReturnSelf();
        $this->transportBuilderMock->expects($this->any())
            ->method('setTemplateVars')->willReturnSelf();
        $this->transportBuilderMock->expects($this->any())
            ->method('setFrom')->willReturnSelf();
        $this->transportBuilderMock->expects($this->any())
            ->method('addTo')->willReturnSelf();
        $this->transportBuilderMock->expects($this->any())
            ->method('getTransport')
            ->willReturn($this->transportInterfaceMock);
        $this->loggerInterfaceMock->expects($this->once())
            ->method('warning')
            ->willReturn(true);

        $result = $this->model->run();
        $this->assertFalse($result);
    }
}
