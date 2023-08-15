<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Config;
use Magento\Support\Model\Report\Config\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private $reportConfig;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Data|MockObject
     */
    private $dataContainerMock;

    protected function setUp(): void
    {
        $this->dataContainerMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->reportConfig = $this->objectManager->getObject(
            Config::class,
            [
                'dataContainer' => $this->dataContainerMock
            ]
        );
    }

    public function testGetGroups()
    {
        $groups = [
            'group1' => [
                'title' => 'Title 1',
                'priority' => 30
            ],
            'group2' => [
                'title' => 'Title 2',
                'priority' => 10
            ],
            'group3' => [
                'title' => 'Title 3',
                'priority' => 20
            ],
            'group4' => [
                'title' => 'Title 4',
                'priority' => 80
            ],
            'group5' => [
                'title' => 'Title 5',
                'priority' => 0
            ]
        ];

        $sortedGroups = [
            'group5' => [
                'title' => 'Title 5',
                'priority' => 0
            ],
            'group2' => [
                'title' => 'Title 2',
                'priority' => 10
            ],
            'group3' => [
                'title' => 'Title 3',
                'priority' => 20
            ],
            'group1' => [
                'title' => 'Title 1',
                'priority' => 30
            ],
            'group4' => [
                'title' => 'Title 4',
                'priority' => 80
            ]
        ];

        $this->dataContainerMock->expects($this->once())
            ->method('get')
            ->with('groups', null)
            ->willReturn($groups);

        $this->assertEquals($sortedGroups, $this->reportConfig->getGroups());
        // Checking that dataContainer::get() is being called only once and values are cached properly
        $this->assertEquals($sortedGroups, $this->reportConfig->getGroups());
    }

    /**
     * @deprecated
     */
    public function testGroupPriorityCompare()
    {
        $this->assertTrue(
            $this->reportConfig->groupPriorityCompare(['priority' => 30], ['priority' => 20])
        );
        $this->assertFalse(
            $this->reportConfig->groupPriorityCompare(['priority' => 30], ['priority' => 30])
        );
        $this->assertFalse(
            $this->reportConfig->groupPriorityCompare(['priority' => 30], ['priority' => 40])
        );
    }

    public function testGetGroupOptions()
    {
        $groups = [
            'group1' => ['title' => 'title1', 'priority' => 90],
            'group2' => ['title' => 'title2', 'priority' => 0],
            'group3' => ['title' => 'title3', 'priority' => 30],
            'group4' => ['title' => 'title4', 'priority' => 20],
            'group5' => ['title' => 'title5', 'priority' => 80]
        ];

        $options = [
            ['label' => 'title2', 'value' => 'group2'],
            ['label' => 'title4', 'value' => 'group4'],
            ['label' => 'title3', 'value' => 'group3'],
            ['label' => 'title5', 'value' => 'group5'],
            ['label' => 'title1', 'value' => 'group1']
        ];

        $this->dataContainerMock->expects($this->any())
            ->method('get')
            ->with('groups', null)
            ->willReturn($groups);

        $this->assertEquals($options, $this->reportConfig->getGroupOptions());
    }

    public function testGetGroupNames()
    {
        $groups = [
            'group1' => [
                'title' => 'Title 1',
                'priority' => 50
            ],
            'group2' => [
                'title' => 'Title 2',
                'priority' => 20
            ],
            'group3' => [
                'title' => 'Title 3',
                'priority' => 40
            ],
            'group4' => [
                'title' => 'Title 4',
                'priority' => 10
            ],
            'group5' => [
                'title' => 'Title 5',
                'priority' => 70
            ]
        ];

        $groupNames = ['group4', 'group2', 'group3', 'group1', 'group5'];

        $this->dataContainerMock->expects($this->once())
            ->method('get')
            ->with('groups', null)
            ->willReturn($groups);

        $this->assertEquals($groupNames, $this->reportConfig->getGroupNames());
    }

    public function testGetSectionNamesByGroup()
    {
        $requestedGroups1 = ['group1', 'group3', 'group4', 'group5'];
        $requestedGroups2 = 'group2,group4,group5';
        $sections1 = ['section31' => [], 'section51' => [], 'section52' => []];
        $sections2 = ['section21' => [], 'section22' => [], 'section51' => [], 'section52' => []];

        $groups = [
            'group1' => [
                'title' => 'Title 1',
                'priority' => 90,
                'sections' => []
            ],
            'group2' => [
                'title' => 'Title 2',
                'priority' => 70,
                'sections' => [
                    'section21' => [],
                    'section22' => []
                ]
            ],
            'group3' => [
                'title' => 'Title 3',
                'priority' => 10,
                'sections' => [
                    'section31' => []
                ]
            ],
            'group4' => [
                'title' => 'Title 4',
                'priority' => 40
            ],
            'group5' => [
                'title' => 'Title 5',
                'priority' => 0,
                'sections' => [
                    'section51' => [],
                    'section52' => []
                ]
            ]
        ];

        $this->dataContainerMock->expects($this->any())
            ->method('get')
            ->with('groups', null)
            ->willReturn($groups);

        $this->assertEquals([], $this->reportConfig->getSectionNamesByGroup([]));
        $this->assertEquals(
            $sections1,
            $this->reportConfig->getSectionNamesByGroup($requestedGroups1)
        );
        $this->assertEquals(
            $sections2,
            $this->reportConfig->getSectionNamesByGroup($requestedGroups2)
        );
    }

    public function testToOptionArray()
    {
        $groups = [
            'group1' => ['title' => 'title1', 'priority' => 30],
            'group2' => ['title' => 'title2', 'priority' => 70],
            'group3' => ['title' => 'title3', 'priority' => 40],
            'group4' => ['title' => 'title4', 'priority' => 90],
            'group5' => ['title' => 'title5', 'priority' => 10]
        ];

        $options = [
            ['label' => 'title5', 'value' => 'group5'],
            ['label' => 'title1', 'value' => 'group1'],
            ['label' => 'title3', 'value' => 'group3'],
            ['label' => 'title2', 'value' => 'group2'],
            ['label' => 'title4', 'value' => 'group4']
        ];

        $this->dataContainerMock->expects($this->any())
            ->method('get')
            ->with('groups', null)
            ->willReturn($groups);

        $this->assertEquals($options, $this->reportConfig->toOptionArray());
    }
}
