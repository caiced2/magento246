<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MsrpStaging\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Msrp\Model\Config;
use Magento\MsrpStaging\Ui\DataProvider\Product\Form\Modifier\MsrpStaging;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test Msrp UI modifier for staging
 */
class MsrpStagingTest extends TestCase
{
    /**
     * @var LocatorInterface|MockObject
     */
    private $locator;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var MsrpStaging
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->locator = $this->createMock(LocatorInterface::class);
        $this->config = $this->createMock(Config::class);
        $this->model = new MsrpStaging(
            $this->locator,
            $this->config,
            new ArrayManager()
        );
    }

    /**
     * Test modify meta
     */
    public function testModifyMeta(): void
    {
        $store = $this->createMock(StoreInterface::class);
        $storeId = 1;
        $store->method('getId')->willReturn($storeId);
        $this->locator->method('getStore')->willReturn($store);
        $this->config->expects($this->once())
            ->method('setStoreId')
            ->with($storeId);
        $meta = [
            'staging' => [
                'advanced_pricing' => [
                    'children' => [
                        'container_msp' => [
                            'children' => [
                                'msrp' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'visible' => 1
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($meta, $this->model->modifyMeta($meta));
    }
}
