<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Environment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Environment\EnvironmentSection;
use Magento\Support\Model\ResourceModel\Report;
use Magento\Support\Model\ResourceModel\Report\Environment\ApacheEnvironment;
use Magento\Support\Model\ResourceModel\Report\Environment\MysqlEnvironment;
use Magento\Support\Model\ResourceModel\Report\Environment\OsEnvironment;
use Magento\Support\Model\ResourceModel\Report\Environment\PhpEnvironment;
use Magento\Support\Model\ResourceModel\Report\Environment\PhpInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EnvironmentSectionTest extends TestCase
{
    /**
     * @var EnvironmentSection
     */
    protected $environmentReport;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var Report\Environment\PhpInfo|MockObject
     */
    protected $phpInfoMock;

    /**
     * @var Report\Environment\OsEnvironment|MockObject
     */
    protected $osEnvironmentMock;

    /**
     * @var Report\Environment\ApacheEnvironment|MockObject
     */
    protected $apacheEnvironmentMock;

    /**
     * @var Report\Environment\MysqlEnvironment|MockObject
     */
    protected $mysqlEnvironmentMock;

    /**
     * @var Report\Environment\PhpEnvironment|MockObject
     */
    protected $phpEnvironmentMock;

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
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->phpInfoMock = $this->getMockBuilder(
            PhpInfo::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->osEnvironmentMock = $this->createMock(
            OsEnvironment::class
        );
        $this->apacheEnvironmentMock = $this->createMock(
            ApacheEnvironment::class
        );
        $this->mysqlEnvironmentMock = $this->createMock(
            MysqlEnvironment::class
        );
        $this->phpEnvironmentMock = $this->createMock(
            PhpEnvironment::class
        );
        $this->environmentReport = $this->objectManagerHelper->getObject(
            EnvironmentSection::class,
            [
                'logger' => $this->loggerMock,
                'phpInfo' => $this->phpInfoMock,
                'osEnvironment' => $this->osEnvironmentMock,
                'apacheEnvironment' => $this->apacheEnvironmentMock,
                'mysqlEnvironment' => $this->mysqlEnvironmentMock,
                'phpEnvironment' => $this->phpEnvironmentMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecuteWithEmptyPhpInfoCollection()
    {
        $expectedResult = [
            'Environment Information' => [
                'headers' => ['Parameter', 'Value'],
                'data' => [],
                'count' => 0
            ]
        ];
        $this->phpInfoMock->expects($this->once())
            ->method('getCollectPhpInfo')
            ->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('error');
        $this->assertSame($expectedResult, $this->environmentReport->generate());
    }

    /**
     * @return void
     */
    public function testExecuteMainFlow()
    {
        $osEnvironment = ['OS', 'Linux'];
        $apacheVersion = ['Apache ver', '2.2'];
        $apacheDocRoot = ['Docuent root', '/var/www'];
        $apacheSrvAddress = ['Server address', '192.168.0.1:80'];
        $apacheRemoteAddress = ['Remote address', '10.10.10.10:80'];
        $apacheLoadedModules = ['Loaded Modules', 'mod_rewrite'];
        $mysqlVersion = ['MySQLServer ver', '5.6'];
        $mysqlEngines = ['Supported engines', 'MyISAM; InnoDB'];
        $mysqlDbAmount = ['DB Amount', '2'];
        $mysqlConfiguration = ['DB Conf', 'Some conf info'];
        $mysqlPlugins = ['DB Plugins', 'Plugins info'];
        $phpVersion = ['PHP version', '5.6'];
        $phpLoadedConf = ['Loaded Conf File', 'php.ini'];
        $phpAdditionalIni = ['Additional ini', '(none)'];
        $phpImportantConfSettings = ['Conf settings', 'Settings list'];
        $phpLoadedModules = ['PHP Modules', 'iconv'];
        $expectedResult = [
            'Environment Information' => [
                'headers' => ['Parameter', 'Value'],
                'data' => [
                    $osEnvironment, $apacheVersion, $apacheDocRoot, $apacheSrvAddress,
                    $apacheRemoteAddress, $apacheLoadedModules, $mysqlVersion, $mysqlEngines,
                    $mysqlDbAmount, $mysqlConfiguration, $mysqlPlugins, $phpVersion,
                    $phpLoadedConf, $phpAdditionalIni, $phpImportantConfSettings, $phpLoadedModules
                ],
                'count' => 16
            ]
        ];
        $this->phpInfoMock->expects($this->any())->method('getCollectPhpInfo')
            ->willReturn([]);
        $this->osEnvironmentMock->expects($this->once())
            ->method('getOsInformation')
            ->willReturn($osEnvironment);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($apacheVersion);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getDocumentRoot')
            ->willReturn($apacheDocRoot);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getServerAddress')
            ->willReturn($apacheSrvAddress);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getRemoteAddress')
            ->willReturn($apacheRemoteAddress);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getLoadedModules')
            ->willReturn($apacheLoadedModules);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($mysqlVersion);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getSupportedEngines')
            ->willReturn($mysqlEngines);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getDbAmount')
            ->willReturn($mysqlDbAmount);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getDbConfiguration')
            ->willReturn($mysqlConfiguration);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getPlugins')
            ->willReturn($mysqlPlugins);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($phpVersion);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getLoadedConfFile')
            ->willReturn($phpLoadedConf);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getAdditionalIniFile')
            ->willReturn($phpAdditionalIni);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getImportantConfigSettings')
            ->willReturn($phpImportantConfSettings);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getLoadedModules')
            ->willReturn($phpLoadedModules);
        $this->assertSame($expectedResult, $this->environmentReport->generate());
    }

    /**
     * @return void
     */
    public function testCleanerEmptyArray()
    {
        $osEnvironment = ['OS', 'Linux'];
        $phpImportantConfSettings = ['Conf settings', 'Settings list'];
        $expectedResult = [
            EnvironmentSection::REPORT_TITLE => [
                'headers' => ['Parameter', 'Value'],
                'data' => [$osEnvironment, $phpImportantConfSettings],
                'count' => 2
            ]
        ];
        $this->phpInfoMock->expects($this->any())->method('getCollectPhpInfo')
            ->willReturn([]);
        $this->osEnvironmentMock->expects($this->once())
            ->method('getOsInformation')
            ->willReturn($osEnvironment);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getImportantConfigSettings')
            ->willReturn($phpImportantConfSettings);
        $this->assertSame($expectedResult, $this->environmentReport->generate());
    }
}
