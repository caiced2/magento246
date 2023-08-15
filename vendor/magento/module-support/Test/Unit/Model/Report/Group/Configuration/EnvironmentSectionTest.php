<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Configuration;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Support\Model\Report\Group\Configuration\EnvironmentSection;
use PHPUnit\Framework\MockObject\MockObject;

class EnvironmentSectionTest extends AbstractConfigurationSectionTest
{
    /**
     * @var EnvironmentSection
     */
    protected $environmentReport;

    /**
     * @var DeploymentConfig|MockObject
     */
    protected $deploymentConfigMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        $this->environmentReport = $this->objectManagerHelper->getObject(
            EnvironmentSection::class,
            [
                'logger' => $this->loggerMock,
                'deploymentConfig' => $this->deploymentConfigMock,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function testGetReportTitle()
    {
        $this->assertSame((string)__('Data from app/etc/env.php'), $this->environmentReport->getReportTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function testGenerate()
    {
        $installDate = 'Thu, 27 Aug 2015 15:28:45 +0300';
        $sensitiveValue = 'sensitive';
        $returnMap = [
            [ConfigOptionsListConstants::CONFIG_PATH_BACKEND, [], ['frontName' => 'admin']],
            [
                ConfigOptionsListConstants::CONFIG_PATH_INSTALL,
                [],
                ['date' => $installDate]
            ],
            [ConfigOptionsListConstants::CONFIG_PATH_CRYPT, [], ['key' => $sensitiveValue]],
            [ConfigOptionsListConstants::CONFIG_PATH_SESSION, [], ['save' => 'files']],
            [
                ConfigOptionsListConstants::CONFIG_PATH_DB,
                [],
                [
                    'table_prefix' => '',
                    'connection' => [
                        'default' => [
                            'host' => 'localhost',
                            'dbname' => 'magento2',
                            'username' => $sensitiveValue,
                            'password' => $sensitiveValue,
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ]
                    ],
                ]
            ],
            [
                ConfigOptionsListConstants::CONFIG_PATH_RESOURCE,
                [],
                ['default_setup' => ['connection' => 'default']]
            ],
            [ConfigOptionsListConstants::CONFIG_PATH_X_FRAME_OPT, [], 'SAMEORIGIN'],
            [
                ConfigOptionsListConstants::CONFIG_PATH_CACHE_TYPES,
                [],
                $this->getCacheTypesSettings(),
            ]
        ];
        $this->deploymentConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap($returnMap);
        $expectedData = [
            ['<backend>', ''],
            [EnvironmentSection::TAB . '<frontName>', 'admin'],
            ['<install>', ''],
            [EnvironmentSection::TAB . '<date>', $installDate],
            ['<crypt>', ''],
            [EnvironmentSection::TAB . '<key>', EnvironmentSection::HIDDEN_VALUE],
            ['<session>', ''],
            [EnvironmentSection::TAB . '<save>', 'files'],
            ['<db>', ''],
            [EnvironmentSection::TAB . '<table_prefix>', ''],
            [EnvironmentSection::TAB . '<connection>', ''],
            [str_repeat(EnvironmentSection::TAB, 2) . '<default>', ''],
            [str_repeat(EnvironmentSection::TAB, 3) . '<host>', 'localhost'],
            [str_repeat(EnvironmentSection::TAB, 3) . '<dbname>', 'magento2'],
            [str_repeat(EnvironmentSection::TAB, 3) . '<username>', EnvironmentSection::HIDDEN_VALUE],
            [str_repeat(EnvironmentSection::TAB, 3) . '<password>', EnvironmentSection::HIDDEN_VALUE],
            [str_repeat(EnvironmentSection::TAB, 3) . '<model>', 'mysql4'],
            [str_repeat(EnvironmentSection::TAB, 3) . '<engine>', 'innodb'],
            [str_repeat(EnvironmentSection::TAB, 3) . '<initStatements>', 'SET NAMES utf8;'],
            [str_repeat(EnvironmentSection::TAB, 3) . '<active>', '1'],
            ['<resource>', ''],
            [EnvironmentSection::TAB . '<default_setup>', ''],
            [str_repeat(EnvironmentSection::TAB, 2) . '<connection>', 'default'],
            ['<x-frame-options>', 'SAMEORIGIN'],
            ['<cache_types>', ''],
            [EnvironmentSection::TAB . '<config>', '1'],
            [EnvironmentSection::TAB . '<layout>', '0'],
            [EnvironmentSection::TAB . '<block_html>', '0'],
            [EnvironmentSection::TAB . '<collections>', '1'],
            [EnvironmentSection::TAB . '<db_ddl>', '1'],
            [EnvironmentSection::TAB . '<eav>', '1'],
            [EnvironmentSection::TAB . '<full_page>', '0'],
            [EnvironmentSection::TAB . '<translate>', '1'],
            [EnvironmentSection::TAB . '<config_integration>', '1'],
            [EnvironmentSection::TAB . '<config_integration_api>', '1'],
            [EnvironmentSection::TAB . '<config_webservice>', '1'],
        ];
        $expectedResult = [
            $this->environmentReport->getReportTitle() => [
                'headers' => [(string)__('Path'), (string)__('Value')],
                'data' => $expectedData,
                'count' => count($expectedData),
            ]
        ];
        $this->assertSame($expectedResult, $this->environmentReport->generate());
    }

    /**
     * @return array
     */
    protected function getCacheTypesSettings()
    {
        return [
            'config' => '1',
            'layout' => '0',
            'block_html' => '0',
            'collections' => '1',
            'db_ddl' => '1',
            'eav' => '1',
            'full_page' => '0',
            'translate' => '1',
            'config_integration' => '1',
            'config_integration_api' => '1',
            'config_webservice' => '1',
        ];
    }
}
