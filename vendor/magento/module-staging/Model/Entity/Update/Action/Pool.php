<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Entity\Update\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Staging\Model\Entity\Update\Action\TransactionPool;
use Magento\Framework\ObjectManagerInterface as ObjectManager;

class Pool
{
    /**
     * @var ActionInterface[]
     */
    protected $actions = [];

    /**
     * @var TransactionPool
     */
    private $transactionPool;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Pool constructor.
     *
     * @param \Magento\Staging\Model\Entity\Update\Action\TransactionPool $transactionPool
     * @param ObjectManager $objectManager
     * @param array $actions
     */
    public function __construct(
        TransactionPool $transactionPool,
        ObjectManager $objectManager,
        array $actions = []
    ) {
        $this->transactionPool = $transactionPool;
        $this->actions = $actions;
        $this->objectManager = $objectManager;
    }

    /**
     * Returns action
     *
     * @param string $entityType
     * @param string $namespace
     * @param string $actionType
     * @return ActionInterface
     * @throws LocalizedException
     */
    public function getAction($entityType, $namespace, $actionType)
    {
        if (!(isset($this->actions[$entityType][$namespace])
            && isset($this->actions[$entityType][$namespace][$actionType]))
        ) {
            throw new \InvalidArgumentException(
                __('The "%1" action type is invalid. Verify the action type and try again.', $actionType)
            );
        }
        $action = $this->objectManager->get($this->actions[$entityType][$namespace][$actionType]);
        if (!is_subclass_of($action, ActionInterface::class)) {
            throw new LocalizedException(__('The action is invalid. Verify the action and try again.'));
        }
        return $action;
    }

    /**
     * @param ActionInterface $actionType
     * @return ActionInterface
     */
    public function getExecutor(ActionInterface $actionType)
    {
        $namespace = get_class($actionType);
        $executor = $this->transactionPool->getExecutor($namespace);
        if (!$executor) {
            return $actionType;
        }
        $executor->setAction($actionType);
        return $executor;
    }
}
