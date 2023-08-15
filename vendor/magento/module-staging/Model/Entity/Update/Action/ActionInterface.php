<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\Update\Action;

/**
 * Interface \Magento\Staging\Model\Entity\Update\Action\ActionInterface
 *
 * @api
 */
interface ActionInterface
{
    /**
     * Execute action
     *
     * @api
     * @param array $params
     * @return mixed
     */
    public function execute(array $params);
}
