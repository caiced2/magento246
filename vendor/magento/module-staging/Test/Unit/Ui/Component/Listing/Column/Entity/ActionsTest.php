<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Ui\Component\Listing\Column\Entity;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Staging\Ui\Component\Listing\Column\Entity\Actions;
use Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionsTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $contextMock;

    /**
     * @var MockObject
     */
    private $urlBuilderMock;

    /**
     * @var MockObject
     */
    private $componentFactoryMock;

    /**
     * @var MockObject
     */
    private $urlProviderMock;

    /**
     * @var Actions
     */
    private $actions;

    protected function setUp(): void
    {
        $processorMock = $this->createMock(Processor::class);
        $this->contextMock = $this->getMockForAbstractClass(ContextInterface::class);
        $this->contextMock->expects($this->never())->method('getProcessor')->willReturn($processorMock);
        $this->urlBuilderMock = $this->createMock(UrlBuilder::class);
        $this->componentFactoryMock = $this->createMock(UiComponentFactory::class);
        $this->urlProviderMock = $this->createMock(
            UrlProviderInterface::class
        );

        $this->actions = new Actions(
            $this->contextMock,
            $this->componentFactoryMock,
            $this->urlBuilderMock,
            'entity_id',
            'entity_id',
            'modalProvider',
            'loaderProvider',
            $this->urlProviderMock,
            [],
            [
                'name' => 'save_action',
            ]
        );
    }

    public function testPrepareDataSource()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'id' => 1000,
                        'entity_id' => 1,
                    ],
                ],
            ],
        ];

        $expectedResult = [
            'data' => [
                'items' => [
                    [
                        'id' => 1000,
                        'entity_id' => 1,
                        'save_action' => [
                            'edit' => [
                                'callback' => [
                                    [
                                        'provider' => 'loaderProvider',
                                        'target' => 'destroyInserted',
                                    ],
                                    [
                                        'provider' => 'loaderProvider',
                                        'target' => 'updateData',
                                        'params' => [
                                            'entity_id' => 1,
                                            'update_id' => 1000,
                                        ],
                                    ],
                                    [
                                        'provider' => 'modalProvider',
                                        'target' => 'openModal',
                                    ],
                                ],
                                'label' => __('View/Edit'),
                            ],
                            'preview' => [
                                'href' => null,
                                'label' => __('Preview'),
                                'target' => '_blank'
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $this->actions->prepareDataSource($dataSource));
    }
}
