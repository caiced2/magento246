<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Entity\Update\Action\Delete;

class SaveAction implements \Magento\Staging\Model\Entity\Update\Action\ActionInterface
{
    /**
     * @var \Magento\Staging\Controller\Adminhtml\Entity\Update\Service
     */
    protected $updateService;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    protected $versionManager;

    /**
     * @var \Magento\Staging\Model\Entity\RetrieverInterface
     */
    protected $entityRetriever;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var \Magento\Staging\Model\Entity\Builder
     */
    protected $builder;

    /**
     * @var \Magento\Staging\Model\EntityStaging
     */
    private $entityStaging;

    /**
     * SaveAction constructor.
     *
     * @param \Magento\Staging\Controller\Adminhtml\Entity\Update\Service $updateService
     * @param \Magento\Staging\Model\VersionManager $versionManager
     * @param \Magento\Staging\Model\Entity\RetrieverInterface $entityRetriever
     * @param \Magento\Staging\Model\EntityStaging $entityStaging
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Staging\Model\Entity\Builder $builder
     * @param string $entityName
     */
    public function __construct(
        \Magento\Staging\Controller\Adminhtml\Entity\Update\Service $updateService,
        \Magento\Staging\Model\VersionManager $versionManager,
        \Magento\Staging\Model\Entity\RetrieverInterface $entityRetriever,
        \Magento\Staging\Model\EntityStaging $entityStaging,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Staging\Model\Entity\Builder $builder,
        $entityName
    ) {
        $this->updateService = $updateService;
        $this->versionManager = $versionManager;
        $this->entityRetriever = $entityRetriever;
        $this->messageManager = $messageManager;
        $this->builder = $builder;
        $this->entityName = $entityName;
        $this->entityStaging = $entityStaging;
    }

    /**
     * Save action
     *
     * @param array $params
     * @throws \Exception
     * @return bool
     */
    public function execute(array $params)
    {
        $this->validateParams($params);
        $this->versionManager->setCurrentVersionId($params['updateId']);
        $stagingData = $params['stagingData'];
        $arguments['origin_in'] = $params['updateId'];
        $update = $this->updateService->createUpdate($stagingData);
        /** @var \Magento\Framework\Model\AbstractModel $entity */
        $entity = $this->entityRetriever->getEntity($params['entityId']);
        $this->versionManager->setCurrentVersionId($update->getId());
        $this->entityStaging->schedule(
            $this->builder->build($entity),
            $this->versionManager->getVersion()->getId(),
            $arguments
        );
        $this->messageManager->addSuccessMessage(
            __('You removed this %1 from the update and saved it in a new one.', $this->entityName)
        );
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
