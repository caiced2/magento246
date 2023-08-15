<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Fixture;

use Magento\Banner\Model\BannerFactory;
use Magento\Banner\Model\ResourceModel\Banner as BannerResourceModel;
use Magento\Framework\DataObject;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;

class Banner implements \Magento\TestFramework\Fixture\RevertibleDataFixtureInterface
{
    private const DEFAULT_VALUE = [
        'name' => 'banner%uniqid%',
        'is_enabled' => true,
        'types' => null,
        'segments' => [],
        'banner_catalog_rules' => [],
        'banner_sales_rules' => [],
        'store_contents' => [
            [
                'store_id' => 0,
                'content' => 'Banner Content%uniqid%'
            ]
        ]
    ];

    /**
     * @var BannerFactory
     */
    private BannerFactory $bannerFactory;

    /**
     * @var BannerResourceModel
     */
    private BannerResourceModel $bannerResourceModel;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $processor;

    /**
     * @param BannerFactory $bannerFactory
     * @param BannerResourceModel $bannerResourceModel
     * @param ProcessorInterface $processor
     */
    public function __construct(
        BannerFactory $bannerFactory,
        BannerResourceModel $bannerResourceModel,
        ProcessorInterface $processor
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->bannerResourceModel = $bannerResourceModel;
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as Banner::DEFAULT_DATA.
     */
    public function apply(array $data = []): ?DataObject
    {
        $model = $this->bannerFactory->create();
        $model->addData($this->prepareData($data));
        $this->bannerResourceModel->save($model);
        return $model;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $model = $this->bannerFactory->create();
        $this->bannerResourceModel->load($model, $data->getId());
        if ($model->getId()) {
            $this->bannerResourceModel->delete($model);
        }
    }

    /**
     * Prepares banner data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_VALUE, $data);

        $storeContents = array_column($data['store_contents'], 'content', 'store_id');

        $data['store_contents'] = $storeContents;

        return $this->processor->process($this, $data);
    }
}
