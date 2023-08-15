<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModel;
use Magento\TargetRule\Model\RuleFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class Rule implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'name' => 'rule%uniqid%',
        'sort_order' => 0,
        'is_active' => 1,
        'apply_to' => \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
    ];

    /**
     * @var ProcessorInterface
     */
    private $dataProcessor;

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param ProcessorInterface $dataProcessor
     * @param ResourceModel $resourceModel
     * @param RuleFactory $ruleFactory
     * @param Json $serializer
     */
    public function __construct(
        ProcessorInterface $dataProcessor,
        ResourceModel $resourceModel,
        RuleFactory $ruleFactory,
        Json $serializer
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->resourceModel = $resourceModel;
        $this->ruleFactory = $ruleFactory;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        /** @var \Magento\TargetRule\Model\Rule $model */
        $model = $this->ruleFactory->create();
        $data = $this->prepareData($data);
        $conditions = $data['conditions'];
        $actions = $data['actions'];
        unset($data['conditions'], $data['actions']);
        $model->setData($this->prepareData($data));

        $model->setConditionsSerialized($this->serializer->serialize($conditions));
        $model->setActionsSerialized($this->serializer->serialize($actions));

        $this->resourceModel->save($model);

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        /** @var \Magento\TargetRule\Model\Rule $model */
        $model = $this->ruleFactory->create();
        $this->resourceModel->load($model, $data->getId());
        if ($model->getId()) {
            $this->resourceModel->delete($model);
        }
    }

    /**
     * Prepare rule data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_DATA, $data);
        $data['conditions'] = $data['conditions'] ?? [];
        $data['actions'] = $data['actions'] ?? [];

        if ($data['conditions'] instanceof DataObject) {
            $data['conditions'] = $data['conditions']->toArray();
        }

        if ($data['actions'] instanceof DataObject) {
            $data['actions'] = $data['actions']->toArray();
        }

        return $this->dataProcessor->process($this, $data);
    }
}
