<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Model\Banner;

use Magento\Backend\Helper\Js;
use Magento\Banner\Model\Banner\Validator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    protected $bannerValidator;

    /**
     * @var StoreManager|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Js|MockObject
     */
    protected $jsHelperMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->storeManagerMock = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsHelperMock = $this->getMockBuilder(Js::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->bannerValidator = $objectManager->getObject(
            Validator::class,
            [
                'storeManager' => $this->storeManagerMock,
                'jsHelper' => $this->jsHelperMock
            ]
        );
    }

    /**
     * @param array $data
     * @param array $currentStores
     * @param array $expected
     *
     * @dataProvider prepareSaveDataDataProvider
     */
    public function testPrepareSaveData($data, $currentStores, $expected)
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->with(true)
            ->willReturn($currentStores);

        $this->assertSame(
            $expected,
            $this->bannerValidator->prepareSaveData($data)
        );
    }

    /**
     * @return array
     */
    public function prepareSaveDataDataProvider()
    {
        return [
            [
                'data' => [
                    'store_contents_not_use' => ['store0' => 'store0', 'store1' => 'store1', 'store3' => 'store3'],
                    'store_contents' => ['store2' => 'store2'],
                    'banner_catalog_rules' => [['rule_id' => '11'], ['rule_id' => '22']],
                    'banner_sales_rules' => [['rule_id' => '33'], ['rule_id' => '44']],
                ],
                'currentStores' => ['store2' => 'store2', 'store3' => 'store3'],
                'expected' => [
                    'store_contents_not_use' => ['store3' => 'store3'],
                    'store_contents' => ['store2' => 'store2'],
                    'banner_catalog_rules' => [11, 22],
                    'banner_sales_rules' => [33, 44],
                    'types' => []
                ],
            ],
        ];
    }
}
