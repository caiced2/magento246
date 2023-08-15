<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Entity\Update\Action\Delete;

class AssignAction implements \Magento\Staging\Model\Entity\Update\Action\ActionInterface
{
    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    protected $versionManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Staging\Controller\Adminhtml\Entity\Update\Service
     */
    protected $updateService;

    /**
     * @var \Magento\Staging\Model\Entity\RetrieverInterface
     */
    protected $entityRetriever;

    /**
     * @var  string
     */
    protected $entityName;

    /**
     * @var \Magento\Staging\Model\Entity\Update\CampaignUpdater
     */
    protected $campaignUpdater;

    /**
     * @var \Magento\Staging\Model\Entity\Builder
     */
    private $builder;

    /**
     * @var \Magento\Staging\Model\EntityStaging
     */
    private $entityStaging;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Staging\Controller\Adminhtml\Entity\Update\Service $updateService
     * @param \Magento\Staging\Model\VersionManager $versionManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Staging\Model\EntityStaging $entityStaging
     * @param \Magento\Staging\Model\Entity\RetrieverInterface $entityRetriever
     * @param \Magento\Staging\Model\Entity\Update\CampaignUpdater $campaignUpdater
     * @param \Magento\Staging\Model\Entity\Builder $builder
     * @param string $entityName
     */
    public function __construct(
        \Magento\Staging\Controller\Adminhtml\Entity\Update\Service $updateService,
        \Magento\Staging\Model\VersionManager $versionManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Staging\Model\EntityStaging $entityStaging,
        \Magento\Staging\Model\Entity\RetrieverInterface $entityRetriever,
        \Magento\Staging\Model\Entity\Update\CampaignUpdater $campaignUpdater,
        \Magento\Staging\Model\Entity\Builder $builder,
        $entityName
    ) {
        $this->updateService = $updateService;
        $this->versionManager = $versionManager;
        $this->messageManager = $messageManager;
        $this->entityRetriever = $entityRetriever;
        $this->campaignUpdater = $campaignUpdater;
        $this->builder = $builder;
        $this->entityName = $entityName;
        $this->entityStaging = $entityStaging;
    }

    /**
     * Assign action
     *
     * @param array $params
     * @return bool
     */
    public function execute(array $params)
    {
        $this->validateParams($params);
        $stagingData = $params['stagingData'];
        $this->versionManager->setCurrentVersionId($params['updateId']);
        $arguments['origin_in'] = $params['updateId'];

        /** @var \Magento\Framework\Model\AbstractModel $entity */
        $entity = $this->entityRetriever->getEntity($params['entityId']);
        $entityToSave = $this->builder->build($entity);
        $newUpdate = $this->updateService->assignUpdate($stagingData);
        $newUpdateId = $newUpdate->getId();
        $this->versionManager->setCurrentVersionId($newUpdateId);

        if (!isset($params['entityData'])) {
            $result = $this->entityStaging->schedule(
                $entityToSave,
                $this->versionManager->getVersion()->getId(),
                $arguments
            );
            if ($result) {
                $this->campaignUpdater->updateCampaignStatus($newUpdate);
            }
            $this->messageManager->addSuccess(
                __('You removed this %1 from the update and saved it in the other one.', $this->entityName)
            );
        }
        return true;
    }

    /**
     * Validate input parameters
     *
     * @param array $params
     * @return void
     */
    protected function validateParams(array $params)
    {
        foreach (['entityId', 'updateId', 'stagingData'] as $requiredParam) {
            if (!isset($params[$requiredParam])) {
                throw new \InvalidArgumentException(
                    __('The required parameter is "%1". Set parameter and try again.', $requiredParam)
                );
            }
        }
    }
}
