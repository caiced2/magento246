<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\Update\Action;

use Magento\Framework\App\ResourceConnection;

class TransactionExecutor implements TransactionExecutorInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ActionInterface
     */
    private $action;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param ActionInterface $action
     * @return void
     */
    public function setAction(ActionInterface $action)
    {
        $this->action = $action;
    }

    /**
     * @param array $params
     * @return boolean|null
     * @throws \Exception
     */
    public function execute(array $params)
    {
        if (!$this->action) {
            throw new \LogicException('Action not exists');
        }
        $connection = $this->resourceConnection->getConnection();
        try {
            $connection->beginTransaction();
            $result = $this->action->execute($params);
            $connection->commit();
            return $result;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
