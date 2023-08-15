<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Stores;

use Magento\Support\Model\Report\Group\Stores\WebsitesTreeSection;

class WebsitesTreeSectionTest extends AbstractTest
{
    protected function setUp(): void
    {
        parent::prepareObjects(WebsitesTreeSection::class);
    }

    public function testGenerate()
    {
        $websites = [
            '1' => $this->getWebsiteMock(
                [
                    'id' => '1',
                    'name' => 'Main Website',
                    'code' => 'base',
                    'is_default' => '1',
                    'default_group_id' => '1',
                    'groups' => [
                        '1' => $this->getStoreMock(
                            [
                                'id' => '1',
                                'name' => 'Main Website Store',
                                'root_category_id' => '2',
                                'default_store_id' => '1',
                                'stores' => [
                                    '1' => $this->getStoreViewMock(
                                        [
                                            'id' => '1',
                                            'name' => 'Default Store View',
                                            'code' => 'default',
                                            'is_active' => '1',
                                            'store_id' => '1'
                                        ]
                                    )
                                ]
                            ]
                        )
                    ]
                ]
            )
        ];
        $expectedResult = [
            (string)__('Websites Tree') => [
                'headers' => [__('ID'), __('Name'), __('Code'), __('Type'), __('Root Category')],
                'data' => [
                    ['1', 'Main Website [*]', 'base', 'website', ''],
                    ['1', '    Main Website Store [*]', '', 'store', 'Default Category'],
                    ['1', '        Default Store View [*]', 'default', 'store view', '']
                ]
            ]
        ];

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);
        $this->categoryCollectionMock->expects($this->any())
            ->method('getItemById')
            ->willReturnMap(
                [
                    ['2', $this->getCategoryMock(['name' => 'Default Category'])]
                ]
            );

        $this->assertEquals($expectedResult, $this->section->generate());
    }
}
