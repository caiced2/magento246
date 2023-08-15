<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Cron;

use Magento\Cron\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Cron\CronJobs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CronJobsTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var CronJobs
     */
    protected $cronJobs;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $cronConfigMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var DirectoryList|MockObject
     */
    protected $directoryListMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->cronConfigMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->cronJobs = $this->objectManagerHelper->getObject(
            CronJobs::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'cronConfig' => $this->cronConfigMock,
                'directoryList' => $this->directoryListMock
            ]
        );
    }

    /**
     * @param array $cronJobs
     * @param array $expectedResult
     * @return void
     * @dataProvider getAllCronJobsDataProvider
     */
    public function testGetAllCronJobs($cronJobs, $expectedResult)
    {
        $this->cronConfigMock->expects($this->once())
            ->method('getJobs')
            ->willReturn($cronJobs);

        $this->assertSame($expectedResult, $this->cronJobs->getAllCronJobs());
    }

    /**
     * @return array
     */
    public function getAllCronJobsDataProvider()
    {
        return [
            [
                'cronJobs' => [
                    'indexer' => [
                        'tax_rules' => ['name' => 'tax_rules']
                    ],
                    'default' => [
                        'clear_cache' => ['name' => 'clear_cache']
                    ]
                ],
                'expectedResult' => [
                    'clear_cache' => ['name' => 'clear_cache', 'group_code' => 'default'],
                    'tax_rules' => ['name' => 'tax_rules', 'group_code' => 'indexer']
                ]
            ],
            [
                'cronJobs' => [],
                'expectedResult' => []
            ]
        ];
    }

    /**
     * @param array $cronJobs
     * @param int $type
     * @param array $expectedResult
     * @return void
     * @dataProvider getCronJobsByTypeDataProvider
     */
    public function testGetCronJobsByType($cronJobs, $type, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->cronJobs->getCronJobsByType($cronJobs, $type));
    }

    /**
     * @return array
     */
    public function getCronJobsByTypeDataProvider()
    {
        $cronJobs = [
            'clear_cache' => ['name' => 'clear_cache', 'instance' => \Magento\Framework\Cache\ClearCache::class],
            'custom_cron' => ['name' => 'custom_cron', 'instance' => 'Vendor\Module\Class'],
            'sitemap' => ['name' => 'sitemap', 'instance' => \Magento\Sitemap\SitemapGenerator::class]
        ];

        return [
            [
                'cronJobs' => $cronJobs,
                'type' => CronJobs::TYPE_CUSTOM,
                'expectedResult' => [
                    'custom_cron' => [
                        'name' => 'custom_cron',
                        'instance' => 'Vendor\Module\Class'
                    ]
                ]
            ],
            [
                'cronJobs' => $cronJobs,
                'type' => CronJobs::TYPE_CORE,
                'expectedResult' => [
                    'clear_cache' => [
                        'name' => 'clear_cache',
                        'instance' => \Magento\Framework\Cache\ClearCache::class
                    ],
                    'sitemap' => [
                        'name' => 'sitemap',
                        'instance' => \Magento\Sitemap\SitemapGenerator::class
                    ]
                ]
            ],
            [
                'cronJobs' => $cronJobs,
                'type' => -1,
                'expectedResult' => []
            ]
        ];
    }

    /**
     * @param array $cronJobs
     * @param array $configurableList
     * @param array $expectedResult
     * @return void
     * @dataProvider getAllConfigurableCronJobsDataProvider
     */
    public function testGetAllConfigurableCronJobs($cronJobs, $configurableList, $expectedResult)
    {
        $this->cronConfigMock->expects($this->once())
            ->method('getJobs')
            ->willReturn($cronJobs);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('crontab')
            ->willReturn($configurableList);

        $this->assertSame($expectedResult, $this->cronJobs->getAllConfigurableCronJobs());
    }

    /**
     * @return array
     */
    public function getAllConfigurableCronJobsDataProvider()
    {
        $cronJobs = [
            'indexer' => [
                'tax_rules' => ['name' => 'tax_rules']
            ],
            'default' => [
                'clear_cache' => ['name' => 'clear_cache']
            ]
        ];

        return [
            [
                'cronJobs' => $cronJobs,
                'configurableList' => [
                    'indexer' => ['jobs' => ['tax_rules' => 'some value']]
                ],
                'expectedResult' => [
                    'tax_rules' => ['name' => 'tax_rules', 'group_code' => 'indexer']
                ]
            ],
            [
                'cronJobs' => $cronJobs,
                'configurableList' => [],
                'expectedResult' => []
            ],
            [
                'cronJobs' => [],
                'configurableList' => [],
                'expectedResult' => []
            ]
        ];
    }

    /**
     * @param string $namespace
     * @param bool $expectedResult
     * @return void
     * @dataProvider isCustomCronJobDataProvider
     */
    public function testIsCustomCronJob($namespace, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->cronJobs->isCustomCronJob($namespace));
    }

    /**
     * @return array
     */
    public function isCustomCronJobDataProvider()
    {
        return [
            ['namespace' => 'Magento\\Cms\\Page', 'expectedResult' => false],
            ['namespace' => '\Magento\Cms\Page', 'expectedResult' => false],
            ['namespace' => 'Vendor\\Module\\Class', 'expectedResult' => true],
            ['namespace' => '\Vendor\Module\Class', 'expectedResult' => true]
        ];
    }

    /**
     * @param array $cron
     * @param string $configValue
     * @param string $expectedResult
     * @return void
     * @dataProvider getCronExpressionDataProvider
     */
    public function testGetCronExpression($cron, $configValue, $expectedResult)
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with($cron['config_path'])
            ->willReturn($configValue);

        $this->assertSame($expectedResult, $this->cronJobs->getCronExpression($cron));
    }

    /**
     * @return array
     */
    public function getCronExpressionDataProvider()
    {
        return [
            [
                'cron' => ['config_path' => null],
                'configValue' => null,
                'expectedResult' => null
            ],
            [
                'cron' => ['config_path' => null, 'schedule' => '*/10 * * * *'],
                'configValue' => null,
                'expectedResult' => '*/10 * * * *'
            ],
            [
                'cron' => ['config_path' => 'some_path', 'schedule' => '*/10 * * * *'],
                'configValue' => '*/5 * * * *',
                'expectedResult' => '*/5 * * * *'
            ],
        ];
    }

    /**
     * @return void
     */
    public function testGetFilePathByNamespace()
    {
        $classNamespace = 'Magento\Cms\Page';
        $e = new \Exception();

        $this->directoryListMock->expects($this->once())
            ->method('getRoot')
            ->willThrowException($e);

        $this->assertSame('n/a', $this->cronJobs->getFilePathByNamespace($classNamespace));
    }

    /**
     * @param array $cron
     * @param array $expectedResult
     * @return void
     * @dataProvider getCronInformationDataProvider
     */
    public function testGetCronInformation($cron, $expectedResult)
    {
        $e = new \Exception();
        $this->directoryListMock->expects($this->any())
            ->method('getRoot')
            ->willThrowException($e);

        $this->assertSame($expectedResult, $this->cronJobs->getCronInformation($cron));
    }

    /**
     * @return array
     */
    public function getCronInformationDataProvider()
    {
        return [
            [
                'cron' => [
                    'name' => 'test',
                    'schedule' => '*/1 * * * *',
                    'instance' => null,
                    'method' => null,
                    'group_code' => 'default'
                ],
                'expectedResult' => [
                    'test',
                    '*/1 * * * *',
                    'n/a',
                    'n/a',
                    'default'
                ]
            ],
            [
                'cron' => [
                    'name' => 'test',
                    'schedule' => null,
                    'instance' => null,
                    'method' => 'testMethod',
                    'group_code' => 'default'
                ],
                'expectedResult' => [
                    'test',
                    'n/a',
                    'n/a',
                    'testMethod',
                    'default'
                ]
            ]
        ];
    }
}
