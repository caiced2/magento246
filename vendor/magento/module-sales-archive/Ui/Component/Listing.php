<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Ui\Component;

use Magento\Ui\Component\Listing\Columns;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentInterface;

class Listing extends \Magento\Ui\Component\Listing
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
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $jsConfig = $this->getJsConfig($this);
        if (isset($jsConfig['provider'])) {
            unset($jsConfig['extends']);
            $this->getContext()->addComponentDefinition($this->getName(), $jsConfig);
        } else {
            $this->getContext()->addComponentDefinition($this->getComponentName(), $jsConfig);
        }
        if ($this->hasData('buttons')) {
            $buttons = $this->getData('buttons');
            if ($this->salesArchiveConfig->isArchiveActive() === false
                || $this->authorizationModel->isAllowed('Magento_SalesArchive::add') === false
            ) {
                unset($buttons['go_to_archive']);
            }
            $this->getContext()->addButtons($buttons, $this);
        }
    }
}
