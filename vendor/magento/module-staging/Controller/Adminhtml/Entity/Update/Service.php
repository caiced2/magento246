<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Controller\Adminhtml\Entity\Update;

use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Update;
use Magento\Framework\Exception\ValidatorException;

class Service
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    protected $metadataPool;

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var \Magento\Staging\Model\UpdateFactory
     */
    protected $updateFactory;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    protected $versionManager;

    /**
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Staging\Model\UpdateFactory $updateFactory
     * @param \Magento\Staging\Model\VersionManager $versionManager
     */
    public function __construct(
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Model\UpdateFactory $updateFactory,
        \Magento\Staging\Model\VersionManager $versionManager
    ) {
        $this->metadataPool = $metadataPool;
        $this->updateRepository = $updateRepository;
        $this->updateFactory = $updateFactory;
        $this->versionManager = $versionManager;
    }

    /**
     * Create a new update
     *
     * @param array $data
     * @return Update
     * @deprecated 100.1.7 Since this functionality moved
     * @see \Magento\Staging\Model\Entity\Update\Action\Save\SaveAction
     */
    public function createUpdate(array $data)
    {
        /** @var Update $update */
        $update = $this->updateFactory->create();
        $hydrator = $this->metadataPool->getHydrator(UpdateInterface::class);
        $hydrator->hydrate($update, $data);
        $update->setIsCampaign(false);
        $this->updateRepository->save($update);
        return $update;
    }

    /**
     * Edit an existing update
     *
     * @param array $data
     * @return Update
     * @deprecated 100.1.7 Since this functionality moved
     * @see \Magento\Staging\Model\Entity\Update\Action\Save\SaveAction
     */
    public function editUpdate(array $data)
    {
        $update = $this->updateRepository->get($data['update_id']);
        $dataStart = strtotime($data['start_time']);
        $dataEnd = strtotime($data['end_time']);
        $updateStart = $update->getStartTime() ? strtotime($update->getStartTime()) : 0;
        $updateEnd = $update->getEndTime() ? strtotime($update->getEndTime()) : 0;
        if ($dataStart != $updateStart || $dataEnd != $updateEnd) {
            unset($data['update_id']);
            return $this->createUpdate($data);
        }
        $hydrator = $this->metadataPool->getHydrator(UpdateInterface::class);
        $hydrator->hydrate($update, $data);
        $this->updateRepository->save($update);
        return $update;
    }

    /**
     * Assign an existing update
     *
     * @param array $data
     * @return Update
     */
    public function assignUpdate(array $data)
    {
        if (!isset($data['select_id'])) {
            throw new \OutOfBoundsException("The 'select_id' value is required.");
        }
        return $this->updateRepository->get($data['select_id']);
    }
}
