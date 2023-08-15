<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerFinance\Test\Unit\Helper;

use Magento\CustomerFinance\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\Export;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    protected $model;

    /**
     * @var StoreManager|MockObject
     */
    protected $storeManagerMock;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        $this->storeManagerMock = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsites'])
            ->getMock();

        $this->model = $helper->getObject(
            Data::class,
            [
                'storeManager' => $this->storeManagerMock,
            ]
        );
    }

    /**
     * @dataProvider populateParamsDataProvider
     */
    public function testPopulateParams($input, $output)
    {
        $website1 = new DataObject(['code' => 'foo_site']);
        $website2 = new DataObject(['code' => 'bar_site']);

        $this->storeManagerMock
            ->method('getWebsites')
            ->willReturn([$website1, $website2]);

        $this->model->populateParams($input);
        $this->assertEquals($output, $input);
    }

    public function populateParamsDataProvider()
    {
        return [
            [
                ['something'],
                ['something']
            ],
            [
                [
                    Export::FILTER_ELEMENT_GROUP => [
                        'store_credit' => [100, 200]
                    ]
                ],
                [
                    Export::FILTER_ELEMENT_GROUP => [
                        'foo_site_store_credit' => [100, 200],
                        'bar_site_store_credit' => [100, 200]
                    ]
                ],
            ]
        ];
    }
}
