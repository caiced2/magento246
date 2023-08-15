<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CheckCatalogSearchPreDispatchObserver implements ObserverInterface
{
    /**
     * Catalog permission helper
     *
     * @var Data
     */
    protected $_catalogPermData;

    /**
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;

    /**
     * Action flag instance
     *
     * @var ActionFlag
     */
    protected $_actionFlag;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param Data $catalogPermData
     * @param ActionFlag $actionFlag
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        Data $catalogPermData,
        ActionFlag $actionFlag
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->_catalogPermData = $catalogPermData;
        $this->_actionFlag = $actionFlag;
    }

    /**
     * Check catalog search availability on predispatch
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        /** @var Action $action */
        $action = $observer->getEvent()->getControllerAction();
        if (!$this->_catalogPermData->isAllowedCatalogSearch() && !$this->_actionFlag->get(
            '',
            Action::FLAG_NO_DISPATCH
        ) && $action->getRequest()->isDispatched()
        ) {
            $this->_actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
            $action->getResponse()->setRedirect($this->_catalogPermData->getLandingPageUrl());
        }

        return $this;
    }
}
