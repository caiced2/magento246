<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Design;

use Magento\Support\Model\Report\Group\Design\FrontendThemesListSection;

class FrontendThemesListSectionTest extends AbstractThemesListSectionTest
{
    /**
     * @var FrontendThemesListSection
     */
    protected $frontendThemesListSectionReport;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->frontendThemesListSectionReport = $this->objectManagerHelper->getObject(
            FrontendThemesListSection::class,
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
            (string)__('Frontend Themes List') => [
                'headers' => [__('Name'), __('Type'), __('Parent')],
                'data' => [
                    ['Acme', 'package'],
                    ['    pink', 'theme', 'blank'],
                    ['Magento', 'package'],
                    ['    blank', 'theme', ''],
                    ['    luma', 'theme', 'blank'],
                    ['    test', 'theme', '']
                ],
            ]
        ];

        $themeCollection = [
            $this->getThemeMock('Acme/pink', $this->parentThemeMock, 'Magento/blank'),
            $this->getThemeMock('Magento/blank', null, null),
            $this->getThemeMock('Magento/luma', $this->parentThemeMock, 'Magento/blank'),
            $this->getThemeMock('Magento/test', null, null),
        ];

        $this->themeCollectionMock->expects($this->once())->method('getItems')->willReturn($themeCollection);
        $this->themeCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($themeCollection));

        $this->assertEquals($expectedResult, $this->frontendThemesListSectionReport->generate());
    }
}
