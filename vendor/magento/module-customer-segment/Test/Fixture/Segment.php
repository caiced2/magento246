<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Fixture;

use Magento\CustomerSegment\Model\ResourceModel\Segment as ResourceModel;
use Magento\CustomerSegment\Model\SegmentFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creating a new segment with user defined conditions
 */
class Segment implements RevertibleDataFixtureInterface
{
    /**
     * @var array Segment post data
     * POST data emulating segment conditions passed by user
     */
    private const DEFAULT_DATA = [
        'name' => 'Segment%uniqid%',
        'description' => null,
        'website_ids' => ['1'],
        'is_active' => '1',
        'apply_to' => \Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS_AND_REGISTERED,
        'conditions' => [
        ]
    ];

    /**
     * @var SegmentFactory object
     */
    private SegmentFactory $segmentFactory;

    /**
     * @var ResourceModel object
     */
    private ResourceModel $resourceModel;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $dataProcessor;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @param SegmentFactory $segmentFactory
     * @param ResourceModel $resourceModel
     * @param ProcessorInterface $dataProcessor
     * @param Json $serializer
     */
    public function __construct(
        SegmentFactory $segmentFactory,
        ResourceModel $resourceModel,
        ProcessorInterface $dataProcessor,
        Json $serializer
    ) {
        $this->segmentFactory = $segmentFactory;
        $this->resourceModel = $resourceModel;
        $this->dataProcessor = $dataProcessor;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function apply(array $data = []): ?DataObject
    {
        /** @var \Magento\CustomerSegment\Model\Segment $model */
        $model = $this->segmentFactory->create();
        $data = $this->prepareData($data);
        $conditions = $data['conditions'];
        unset($data['conditions']);
        $model->setData($this->prepareData($data));

        $serializedConditionData = $this->serializer->serialize($conditions);
        $model->setConditionsSerialized($serializedConditionData);
        $this->resourceModel->save($model);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function revert(DataObject $data): void
    {
        /** @var \Magento\CustomerSegment\Model\Segment $model */
        $model = $this->segmentFactory->create();
        $this->resourceModel->load($model, $data->getId());
        if ($model->getId()) {
            $this->resourceModel->delete($model);
        }
    }

    /**
     * Prepare Customer segment condition data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_DATA, $data);
        $data['conditions'] = $data['conditions'] ?? [];

        if ($data['conditions'] instanceof DataObject) {
            $data['conditions'] = $data['conditions']->toArray();
        } else {
            $conditions = $data['conditions'];
            $data['conditions'] = RootCondition::DEFAULT_DATA;
            foreach ($conditions as $condition) {
                $data['conditions']['conditions'][] = $condition instanceof DataObject
                    ? $condition->toArray()
                    : $condition;
            }
        }

        return $this->dataProcessor->process($this, $data);
    }
}
