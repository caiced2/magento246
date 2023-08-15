<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedSalesRule\Model\Indexer;

/**
 * Class SalesRule
 */
class SalesRule implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var SalesRule\Action\FullFactory
     */
    protected $fullActionFactory;

    /**
     * @var SalesRule\Action\RowsFactory
     */
    protected $rowsActionFactory;

    /**
     * @param SalesRule\Action\FullFactory $fullActionFactory
     * @param SalesRule\Action\RowsFactory $rowsActionFactory
     */
    public function __construct(
        SalesRule\Action\FullFactory $fullActionFactory,
        SalesRule\Action\RowsFactory $rowsActionFactory
    ) {
        $this->fullActionFactory = $fullActionFactory;
        $this->rowsActionFactory = $rowsActionFactory;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     */
    public function execute($ids)
    {
        $this->executeAction($ids);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        $this->fullActionFactory->create()->execute();
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->executeAction($ids);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        $this->executeAction([$id]);
    }

    /**
     * Execute action for single entity or list of entities
     *
     * @param int[] $ids
     * @return $this
     */
    protected function executeAction($ids)
    {
        $ids = array_unique($ids);
        /** @var SalesRule\Action\Rows $action */
        $action = $this->rowsActionFactory->create();
        $action->execute($ids);
        return $this;
    }
}
