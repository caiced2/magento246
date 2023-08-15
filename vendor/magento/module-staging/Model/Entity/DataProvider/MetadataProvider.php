<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;

/**
 * Class MetadataProvider
 */
class MetadataProvider
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var string
     */
    protected $requestFieldName;

    /**
     * @param RequestInterface $request
     * @param UpdateRepositoryInterface $updateRepository
     * @param string $requestFieldName
     */
    public function __construct(
        RequestInterface $request,
        UpdateRepositoryInterface $updateRepository,
        $requestFieldName
    ) {
        $this->request = $request;
        $this->updateRepository = $updateRepository;
        $this->requestFieldName = $requestFieldName;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        $metadata = [];
        $updateId = (int)$this->request->getParam('update_id');
        $entityId = (int)$this->request->getParam($this->requestFieldName);
        if ($entityId && $updateId) {
            $update = $this->updateRepository->get($updateId);
            $isCampaign = (bool)$update->getIsCampaign();
            $metadata['staging']['children'] = [
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
        } else {
            $metadata['staging']['children'] = [
                'staging_save' => [
                    'children' => [
                        'staging_save_mode' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'description' => __('Save as a New Update'),
                                    ],
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
                                        'description' => __('Assign to Existing Update'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        return $metadata;
    }
}
