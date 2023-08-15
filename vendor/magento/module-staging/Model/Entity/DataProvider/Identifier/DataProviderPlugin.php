<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\DataProvider\Identifier;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;

class DataProviderPlugin
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
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @param RequestInterface $request
     * @param UpdateRepositoryInterface $updateRepository
     * @param VersionManager $versionManager
     */
    public function __construct(
        RequestInterface $request,
        UpdateRepositoryInterface $updateRepository,
        VersionManager $versionManager
    ) {
        $this->request = $request;
        $this->updateRepository = $updateRepository;
        $this->versionManager = $versionManager;
    }

    /**
     * @param DataProviderInterface $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetData(DataProviderInterface $subject, \Closure $proceed)
    {
        $updateId = (int)$this->request->getParam('update_id');
        $entityId = (int)$this->request->getParam($subject->getRequestFieldName());

        $update = null;
        try {
            $update = $this->updateRepository->get($updateId);
            $this->versionManager->setCurrentVersionId($update->getId());
        } catch (NoSuchEntityException $e) {
        }

        $result = $proceed();

        if ($entityId && $update && isset($result[$entityId])) {
            $result[$entityId] = array_replace_recursive(
                $result[$entityId],
                [
                    'update_id' => $update->getId(),
                ]
            );
        }
        return $result;
    }
}
