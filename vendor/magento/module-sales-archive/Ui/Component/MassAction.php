<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Ui\Component;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns;
use Magento\Framework\View\Element\UiComponentInterface;

class MassAction extends \Magento\Ui\Component\MassAction
{
    /**
     * @var \Magento\SalesArchive\Model\Config $_salesArchiveConfig
     */
    protected $salesArchiveConfig;

    /**
     * @var \Magento\Framework\AuthorizationInterface $_authModel
     */
    protected $authorizationModel;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param \Magento\SalesArchive\Model\Config $config
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param UiComponentInterface[] $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        \Magento\SalesArchive\Model\Config $config,
        \Magento\Framework\AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        $this->salesArchiveConfig = $config;
        $this->authorizationModel = $authorization;
        parent::__construct($context, $components, $data);
    }

    /**
     * Register component
     *
     * @return void
     */
    public function prepare()
    {
        if ($this->salesArchiveConfig->isArchiveActive() === false
            || $this->authorizationModel->isAllowed('Magento_SalesArchive::add') === false
        ) {
            $config = $this->getData('config');
            if (isset($config['actions'])) {

                unset($config['actions']['add_order_to_archive']);
                $this->setData('config', $config);
            }
            if ($this->getComponent('add_order_to_archive')) {
                unset($this->components['add_order_to_archive']);
            }
        }
        parent::prepare();
    }
}
