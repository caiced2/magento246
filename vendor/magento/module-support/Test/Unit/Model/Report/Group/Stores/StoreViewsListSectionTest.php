<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Stores;

use Magento\Support\Model\Report\Group\Stores\StoreViewsListSection;

class StoreViewsListSectionTest extends AbstractTest
{
    protected function setUp(): void
    {
        parent::prepareObjects(StoreViewsListSection::class);
    }

    public function testGenerate()
    {
        $storeViews = [
            '1' => $this->getStoreViewMock(
                [
                    'id' => '1',
                    'name' => 'Default Store View',
                    'code' => 'default',
                    'is_active' => '1',
                    'group' => $this->getStoreMock(
                        [
                            'id' => '1',
                            'name' => 'Main Website Store'
                        ]
                    )
                ]
            )
        ];
        $expectedResult = [
            (string)__('Store Views List') => [
                'headers' => [__('ID'), __('Name'), __('Code'), __('Enabled'), __('Store')],
                'data' => [
                    ['1', 'Default Store View', 'default', 'Yes', 'Main Website Store {ID:1}']
                ]
            ]
        ];

        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->willReturn($storeViews);

        $this->assertEquals($expectedResult, $this->section->generate());
    }
}
