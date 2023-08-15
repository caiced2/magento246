<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;

class UpdateRepositoryCache implements UpdateRepositoryInterface
{
    /**
     * @var UpdateRepository
     */
    private $updateRepository;

    /**
     * @var UpdateInterface[]
     */
    private $registry = [];

    /**
     * @param UpdateRepository $updateRepository
     */
    public function __construct(UpdateRepository $updateRepository)
    {
        $this->updateRepository = $updateRepository;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        return $this->updateRepository->getList($criteria);
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if (!isset($this->registry[$id])) {
            $this->registry[$id] = $this->updateRepository->get($id);
        }

        return $this->registry[$id];
    }

    /**
     * @inheritdoc
     */
    public function delete(UpdateInterface $entity)
    {
        $entityId = $entity->getId();
        $rollbackId = $entity->getRollbackId();
        $result = $this->updateRepository->delete($entity);
        unset($this->registry[$entityId]);
        if ($rollbackId) {
            unset($this->registry[$rollbackId]);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function save(UpdateInterface $entity)
    {
        $entity = $this->updateRepository->save($entity);
        unset($this->registry[$entity->getId()]);

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getVersionMaxIdByTime($timestamp)
    {
        return $this->updateRepository->getVersionMaxIdByTime($timestamp);
    }
}
