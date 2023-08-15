<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\DataProvider\MetadataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetadataProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var MetadataProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);

        $this->provider = new MetadataProvider(
            $this->requestMock,
            $this->updateRepositoryMock,
            'entity_id'
        );
    }

    public function testGetMetadataForExistingUpdate()
    {
        $updateId = 1;
        $isCampaign = true;
        $updateName = 'Update Name';
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(1);
        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $updateMock->expects($this->any())->method('getName')->willReturn($updateName);
        $updateMock->expects($this->any())->method('getIsCampaign')->willReturn($isCampaign);

        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);

        $expectedResult['staging']['children'] = [
            'staging_save' => [
                'children' => [
                    'staging_save_mode' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'description' => __('Edit Existing Update'),
                                ],
                            ],
                        ],
                    ],
                    'staging_save_name' => [
                        'arguments' => [
                            'data' => [
                                'config' => ['disabled' => $isCampaign],
                            ],
                        ],
                    ],
                    'staging_save_description' => [
                        'arguments' => [
                            'data' => [
                                'config' => ['disabled' => $isCampaign],
                            ],
                        ],
                    ],
                    'staging_save_start_date' => [
                        'arguments' => [
                            'data' => [
                                'config' => ['disabled' => $isCampaign],
                            ],
                        ],
                    ],
                    'staging_save_end_time' => [
                        'arguments' => [
                            'data' => [
                                'config' => ['disabled' => $isCampaign],
                            ],
                        ],
                    ],
                ],
            ],
            'staging_select' => [
                'children' => [
                    'staging_select_mode' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'description' => __('Assign to Another Update'),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $this->provider->getMetadata());
    }
}
