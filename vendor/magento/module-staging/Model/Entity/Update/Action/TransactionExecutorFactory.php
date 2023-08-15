<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\Update\Action;

/**
 * Factory class for @see \Magento\Staging\Model\Entity\Update\Action\TransactionExecutor
 */
class TransactionExecutorFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $instanceName;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Magento\Staging\Model\Entity\Update\Action\TransactionExecutor::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * @return \Magento\Staging\Model\Entity\Update\Action\TransactionExecutor
     */
    public function create()
    {
        return $this->objectManager->get($this->instanceName);
    }
}
