<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\ResourceModel\Report\Environment;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\DataFormatter;
use Magento\Support\Model\ResourceModel\Report\Environment\MysqlEnvironment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MysqlEnvironmentTest extends TestCase
{
    /**
     * @var MysqlEnvironment
     */
    protected $mysqlEnvironment;

    /**
     * @var ModuleResource|MockObject
     */
    protected $resourceMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $resourceConnectionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var DataFormatter|MockObject
     */
    protected $dataFormatterMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->resourceConnectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->dataFormatterMock = $this->createMock(DataFormatter::class);
        $this->createGeneralObjForTests();
    }

    protected function createGeneralObjForTests()
    {
        $this->resourceMock = $this->createMock(ModuleResource::class);
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->resourceConnectionMock);
        $this->mysqlEnvironment = $this->objectManagerHelper->getObject(
            MysqlEnvironment::class,
            [
                'resource' => $this->resourceMock,
                'logger' => $this->loggerMock,
                'dataFormatter' => $this->dataFormatterMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetVersionWithoutMethod()
    {
        $expectedResult = ['MySQL Server Version', 'n/a'];
        $this->assertSame($expectedResult, $this->mysqlEnvironment->getVersion());
    }

    /**
     * @return void
     */
    public function testGetVersionWithMethod()
    {
        $expectedResult = ['MySQL Server Version', '5.6.24'];
        $this->resourceConnectionMock = $this->getMockForAbstractClass(
            AdapterInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['getServerVersion']
        );
        $this->resourceConnectionMock->expects($this->any())
            ->method('getServerVersion')
            ->willReturn('5.6.24');
        $this->createGeneralObjForTests();
        $this->assertSame($expectedResult, $this->mysqlEnvironment->getVersion());
    }

    /**
     * @return void
     */
    public function testGetSupportedEnginesWithException()
    {
        $exception = new \Exception('Some errors with DB');
        $this->resourceConnectionMock->expects($this->once())
            ->method('fetchAll')
            ->with('SHOW ENGINES')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($exception);
        $this->assertSame(['MySQL Supported Engines', 'n/a'], $this->mysqlEnvironment->getSupportedEngines());
    }

    /**
     * @param array $enginesList
     * @param array $expectedResult
     * @return void
     * @dataProvider getSupportedEnginesDataProvider
     */
    public function testGetSupportedEngines($enginesList, $expectedResult)
    {
        $this->resourceConnectionMock->expects($this->once())
            ->method('fetchAll')
            ->with('SHOW ENGINES')
            ->willReturn($enginesList);
        $this->assertSame($expectedResult, $this->mysqlEnvironment->getSupportedEngines());
    }

    /**
     * @return array
     */
    public function getSupportedEnginesDataProvider()
    {
        return [
            [
                'enginesList' => [
                    ['Engine' => 'DISABLED', 'Support' => 'YES'],
                    ['Engine' => 'MyISAM', 'Support' => 'YES'],
                    ['Engine' => 'InnoDB', 'Support' => 'DEFAULT'],
                    ['Engine' => 'CSV', 'Support' => 'NO'],
                ],
                'expectedResult' => [
                    'MySQL Supported Engines', 'MyISAM; InnoDB; '
                ]
            ],
            [
                'enginesList' => [],
                'expectedResult' => [
                    'MySQL Supported Engines', 'n/a'
                ]
            ]
        ];
    }

    /**
     * @return void
     */
    public function testGetDbAmountWithException()
    {
        $exception = new \Exception('Some errors with DB');
        $this->resourceConnectionMock->expects($this->once())
            ->method('fetchAll')
            ->with('SHOW DATABASES')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($exception);
        $this->assertSame(['MySQL Databases Present', 'n/a'], $this->mysqlEnvironment->getDbAmount());
    }

    /**
     * @return void
     */
    public function testGetDbAmount()
    {
        $databasesList = [
            ['Database' => 'magento2ce'],
            ['Database' => 'phpmyadmin'],
        ];
        $this->resourceConnectionMock->expects($this->once())
            ->method('fetchAll')
            ->with('SHOW DATABASES')
            ->willReturn($databasesList);
        $this->assertSame(['MySQL Databases Present', 2], $this->mysqlEnvironment->getDbAmount());
    }

    /**
     * @return void
     */
    public function testGetDbConfigurationWithException()
    {
        $exception = new \Exception('Some errors with DB');
        $this->resourceConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with('SHOW GLOBAL VARIABLES')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($exception);
        $this->assertSame(['MySQL Configuration', 'n/a'], $this->mysqlEnvironment->getDbConfiguration());
    }

    /**
     * @param array $variablesList
     * @param string $formattedBytes
     * @param array $expectedResult
     * @return void
     * @dataProvider getDbConfigurationDataProvider
     */
    public function testGetDbConfiguration($variablesList, $formattedBytes, $expectedResult)
    {
        $this->resourceConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with('SHOW GLOBAL VARIABLES')
            ->willReturn($variablesList);
        $this->dataFormatterMock->expects($this->any())
            ->method('formatBytes')
            ->willReturn($formattedBytes);
        $this->assertSame($expectedResult, $this->mysqlEnvironment->getDbConfiguration());
    }

    /**
     * @return array
     */
    public function getDbConfigurationDataProvider()
    {
        return [
            [
                'variablesList' => [
                    'datadir' => ['Variable_name' => 'datadir', 'Value' => '/var/mysql/data'],
                    'innodb_buffer_pool_size' => ['Variable_name' => 'innodb_buffer_pool_size', 'Value' => '1024'],
                    'other_config' => ['Variable_name' => 'other_config', 'Value' => 'n/a'],
                ],
                'formattedBytes' => '1024B',
                'expectedResult' => [
                    'MySQL Configuration',
                    'datadir => "/var/mysql/data"' . "\n" . 'innodb_buffer_pool_size => "1024B"'
                ]
            ],
            [
                'variablesList' => [],
                'formattedBytes' => null,
                'expectedResult' => ['MySQL Configuration', 'n/a']
            ]
        ];
    }

    /**
     * @return void
     */
    public function testGetPluginsWithException()
    {
        $exception = new \Exception('Some errors with DB');
        $this->resourceConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with('SHOW PLUGINS')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($exception);
        $this->assertSame(['MySQL Plugins', 'n/a'], $this->mysqlEnvironment->getPlugins());
    }

    /**
     * @param array $pluginsList
     * @param array $expectedResult
     * @return void
     * @dataProvider getPluginsDataProvider
     */
    public function testGetPlugins($pluginsList, $expectedResult)
    {
        $this->resourceConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with('SHOW PLUGINS')
            ->willReturn($pluginsList);
        $this->assertSame($expectedResult, $this->mysqlEnvironment->getPlugins());
    }

    /**
     * @return array
     */
    public function getPluginsDataProvider()
    {
        return [
            [
                'pluginsList' => [
                    'binlog' => ['Name' => 'binlog', 'Status' => 'ACTIVE'],
                    'FEDERATED' => ['Name' => 'FEDERATED', 'Status' => 'DISABLED'],
                ],
                'expectedResult' => ['MySQL Plugins', 'binlog' . "\n" . '-disabled- FEDERATED']
            ],
            [
                'pluginsList' => [],
                'expectedResult' => ['MySQL Plugins', 'n/a']
            ],
        ];
    }
}
