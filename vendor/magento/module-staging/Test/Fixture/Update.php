<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\UpdateFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class Update implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'id' => null,
        'name' => 'stagingupdate%uniqid%',
        'description' => '',
        'start_time' => '+1 day midnight',
        'end_time' => '+1 day 11:59:00 pm',
        'is_campaign' => false,
        'rollback_id' => null,
        'is_rollback' => false,
    ];

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $dataProcessor;

    /**
     * @var UpdateRepositoryInterface
     */
    private UpdateRepositoryInterface $updateRepository;

    /**
     * @var UpdateFactory
     */
    private UpdateFactory $updateFactory;

    /**
     * @param ProcessorInterface $dataProcessor
     * @param UpdateRepositoryInterface $updateRepository
     * @param UpdateFactory $updateFactory
     */
    public function __construct(
        ProcessorInterface $dataProcessor,
        UpdateRepositoryInterface $updateRepository,
        UpdateFactory $updateFactory
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->updateRepository = $updateRepository;
        $this->updateFactory = $updateFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $data = array_merge(self::DEFAULT_DATA, $data);
        $data = $this->dataProcessor->process($this, $data);
        $data['start_time'] = date('Y-m-d H:i:s', strtotime($data['start_time']));
        if ($data['end_time']) {
            $data['end_time'] = date('Y-m-d H:i:s', strtotime($data['end_time']));
        }
        $update = $this->updateFactory->create(['data' => $data]);
        $this->updateRepository->save($update);
        return $update;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $update = $this->updateRepository->get($data->getId());
        $this->updateRepository->delete($update);
    }
}
