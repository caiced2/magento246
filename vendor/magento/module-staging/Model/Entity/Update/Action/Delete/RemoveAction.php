<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Entity\Update\Action\Delete;

use Magento\Framework\Message\ManagerInterface;
use Magento\Staging\Model\Entity\Update\Action\ActionInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\Entity\RetrieverInterface;

class RemoveAction implements ActionInterface
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var RetrieverInterface
     */
    protected $entityRetriever;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var EntityStaging
     */
    private $entityStaging;

    /**
     * Initialize dependencies.
     *
     * @param VersionManager $versionManager
     * @param ManagerInterface $messageManager
     * @param EntityStaging $entityStaging
     * @param RetrieverInterface $entityRetriever
     * @param string $entityName
     */
    public function __construct(
        VersionManager $versionManager,
        ManagerInterface $messageManager,
        EntityStaging $entityStaging,
        RetrieverInterface $entityRetriever,
        $entityName
    ) {
        $this->versionManager = $versionManager;
        $this->messageManager = $messageManager;
        $this->entityRetriever = $entityRetriever;
        $this->entityName = $entityName;
        $this->entityStaging = $entityStaging;
    }

    /**
     * Remove action
     *
     * @param array $params
     * @return bool
     */
    public function execute(array $params)
    {
        $this->validateParams($params);
        // delete entity in current version
        $this->versionManager->setCurrentVersionId($params['updateId']);
        $entity = $this->entityRetriever->getEntity($params['entityId']);
        $this->entityStaging->unschedule($entity, $this->versionManager->getVersion()->getId());
        $this->messageManager->addSuccess(
            __('You removed this %1 from the update.', $this->entityName)
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
        foreach (['entityId', 'updateId'] as $requiredParam) {
            if (!isset($params[$requiredParam])) {
                throw new \InvalidArgumentException(
                    __('The required parameter is "%1". Set parameter and try again.', $requiredParam)
                );
            }
        }
    }
}
