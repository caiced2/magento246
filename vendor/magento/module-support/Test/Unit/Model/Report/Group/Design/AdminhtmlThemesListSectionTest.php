<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Design;

use Magento\Support\Model\Report\Group\Design\AdminhtmlThemesListSection;

class AdminhtmlThemesListSectionTest extends AbstractThemesListSectionTest
{
    /**
     * @var AdminhtmlThemesListSection
     */
    protected $adminhtmlThemesListSectionReport;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->adminhtmlThemesListSectionReport = $this->objectManagerHelper->getObject(
            AdminhtmlThemesListSection::class,
            [
                'themeCollectionFactory' => $this->themeCollectionFactoryMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGenerate()
    {
        $expectedResult = [
            (string)__('Adminhtml Themes List') => [
                'headers' => [__('Name'), __('Type'), __('Parent')],
                'data' => [
                    ['Acme', 'package'],
                    ['    backend', 'theme', 'backend'],
                    ['Magento', 'package'],
                    ['    backend', 'theme', ''],
                    ['    backend2', 'theme', 'backend']
                ],
            ]
        ];

        $themeCollection = [
            $this->getThemeMock('Acme/backend', $this->parentThemeMock, 'Magento/backend'),
            $this->getThemeMock('Magento/backend', null, null),
            $this->getThemeMock('Magento/backend2', $this->parentThemeMock, 'Magento/backend')
        ];

        $this->themeCollectionMock->expects($this->once())->method('getItems')->willReturn($themeCollection);
        $this->themeCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($themeCollection));

        $this->assertEquals($expectedResult, $this->adminhtmlThemesListSectionReport->generate());
    }
}
