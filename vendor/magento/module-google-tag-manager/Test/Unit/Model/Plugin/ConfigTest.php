<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Model\Plugin\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /** @var Config */
    protected $config;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\PageCache\Model\Config|MockObject */
    protected $pageCacheConfig;

    /** @var TypeListInterface|MockObject */
    protected $typeListInterface;

    protected function setUp(): void
    {
        $this->pageCacheConfig = $this->createMock(\Magento\PageCache\Model\Config::class);
        $this->typeListInterface = $this->getMockForAbstractClass(TypeListInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->config = $this->objectManagerHelper->getObject(
            Config::class,
            [
                'config' => $this->pageCacheConfig,
                'typeList' => $this->typeListInterface
            ]
        );
    }

    /**
     * @param bool $enabled
     * @param mixed $expects
     *
     * @dataProvider afterSaveDataProvider
     */
    public function testAfterSave($enabled, $expects)
    {
        $config = $this->createMock(\Magento\Config\Model\Config::class);

        $this->pageCacheConfig->expects($this->atLeastOnce())->method('isEnabled')->willReturn($enabled);
        $this->typeListInterface->expects($expects)->method('invalidate')->with('full_page');
        $this->assertSame($config, $this->config->afterSave($config, $config));
    }

    public function afterSaveDataProvider()
    {
        return [
            [true, $this->atLeastOnce()],
            [false, $this->never()],
        ];
    }
}
