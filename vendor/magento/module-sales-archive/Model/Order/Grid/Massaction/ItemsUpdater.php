<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Model\Order\Grid\Massaction;

class ItemsUpdater implements \Magento\Framework\View\Layout\Argument\UpdaterInterface
{
    /**
     * @var \Magento\SalesArchive\Model\Config $_salesArchiveConfig
     */
    protected $_salesArchiveConfig;

    /**
     * @var \Magento\Framework\AuthorizationInterface $_authModel
     */
    protected $_authorizationModel;

    /**
     * @param \Magento\SalesArchive\Model\Config $config
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param array $data
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\SalesArchive\Model\Config $config,
        \Magento\Framework\AuthorizationInterface $authorization,
        $data = []
    ) {
        $this->_salesArchiveConfig = $config;
        $this->_authorizationModel = $authorization;
    }

    /**
     * Remove massaction items in case they disallowed for user
     * @param mixed $argument
     * @return mixed
     */
    public function update($argument)
    {
        if ($this->_salesArchiveConfig->isArchiveActive() === false
            || $this->_authorizationModel->isAllowed('Magento_SalesArchive::add') === false
        ) {
            unset($argument['add_order_to_archive']);
        }

        return $argument;
    }
}
