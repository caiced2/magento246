<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Test\Unit\Model;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\AsyncOrder\Model\AsyncGlobalConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AsyncGlobalConfigTest extends TestCase
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $globalConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AsyncGlobalConfig
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->globalConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->config = $this->createMock(Config::class);

        $this->model = $objectManager->getObject(
            AsyncGlobalConfig::class,
            [
                'deploymentConfig' => $this->deploymentConfig,
                'globalConfig' => $this->globalConfig,
                'config' => $this->config
            ]
        );
    }

    /**
     * @param bool $asyncGrid
     * @param bool $asyncCheckout
     * @param bool $expectedResult
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(bool $asyncGrid, bool $asyncCheckout, bool $expectedResult): void
    {
        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            OrderManagement::ASYNC_ORDER_OPTION_PATH
        )->willReturn($asyncCheckout);

        $this->globalConfig->expects(
            $this->once()
        )->method('getValue')->with(
            'dev/grid/async_indexing'
        )->willReturn($asyncGrid);

        $actualResult = $this->model->getValue('dev/grid/async_indexing', 'default', null);
        $this->assertEquals(
            $expectedResult,
            $actualResult
        );
    }

    /**
     * @param bool $isSetFlag
     * @param bool $expectedResult
     * @dataProvider isSetFlagDataProvider
     */
    public function testIsSetFlag(bool $isSetFlag, bool $expectedResult): void
    {
        $this->config->expects(
            $this->once()
        )->method('isSetFlag')->with(
            'dev/grid/async_indexing'
        )->willReturn($isSetFlag);

        $actualResult = $this->model->isSetFlag('dev/grid/async_indexing', 'default', null);
        $this->assertEquals(
            $expectedResult,
            $actualResult
        );
    }
    
    public function getValueDataProvider(): array
    {
        return [
            [
                'asyncGrid' => true,
                'asyncCheckout' => true,
                'expectedResult' => false,
            ],
            [
                'asyncGrid' => true,
                'asyncCheckout' => false,
                'expectedResult' => true,
            ],
            [
                'asyncGrid' => false,
                'asyncCheckout' => true,
                'expectedResult' => false,
            ],
            [
                'asyncGrid' => false,
                'asyncCheckout' => false,
                'expectedResult' => false,
            ]
        ];
    }

    public function isSetFlagDataProvider(): array
    {
        return [
            [
                'isSetFlag' => true,
                'expectedResult' => true,
            ],
            [
                'isSetFlag' => false,
                'expectedResult' => false,
            ]
        ];
    }
}
