<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity\Update\Action;

/**
 * Interface \Magento\Staging\Model\Entity\Update\Action\TransactionExecutorInterface
 *
 * @api
 */
interface TransactionExecutorInterface extends ActionInterface
{
    /**
     * @param ActionInterface $action
     * @return mixed
     */
    public function setAction(ActionInterface $action);
}
