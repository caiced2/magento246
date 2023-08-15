<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Checks permissions for controller actions.
 */
class ControllerActionPredispatchObserver implements ObserverInterface
{
    /**
     * Instance of http request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * Edit Catalog Rules flag
     *
     * @var boolean
     */
    protected $_canEditCatalogRules;

    /**
     * Edit Sales Rules flag
     *
     * @var boolean
     */
    protected $_canEditSalesRules;

    /**
     * Edit Reminder Rules flag
     *
     * @var boolean
     */
    protected $_canEditReminderRules;

    /**
     * \Magento\Reminder flag
     *
     * @var boolean
     */
    protected $_isEnterpriseReminderEnabled;

    /**
     * @param \Magento\PromotionPermissions\Helper\Data $promoPermData
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\PromotionPermissions\Helper\Data $promoPermData,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->_request = $request;
        $this->_canEditCatalogRules = $promoPermData->getCanAdminEditCatalogRules();
        $this->_canEditSalesRules = $promoPermData->getCanAdminEditSalesRules();
        $this->_canEditReminderRules = $promoPermData->getCanAdminEditReminderRules();

        $this->_isEnterpriseReminderEnabled = $moduleManager->isEnabled('Magento_Reminder');
    }

    /**
     * Handle controller_action_predispatch event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $controllerAction = $observer->getControllerAction();
        $controllerActionName = $this->_request->getActionName();
        // Forbidden action names should be lowercase.
        $forbiddenActionNames = ['new', 'applyrules', 'save', 'delete', 'run'];

        if (in_array(strtolower($controllerActionName), $forbiddenActionNames)
            &&
            (!$this->_canEditSalesRules &&
                $controllerAction instanceof \Magento\SalesRule\Controller\Adminhtml\Promo\Quote ||
                !$this->_canEditCatalogRules &&
                $controllerAction instanceof \Magento\CatalogRule\Controller\Adminhtml\Promo\Catalog ||
                $this->_isEnterpriseReminderEnabled &&
                !$this->_canEditReminderRules &&
                $controllerAction instanceof \Magento\Reminder\Controller\Adminhtml\Reminder
            )
        ) {
            $this->_forward();
        }
    }

    /**
     * Forward current request
     *
     * @param string $action
     * @param string $module
     * @param string $controller
     * @return void
     */
    protected function _forward($action = 'denied', $module = null, $controller = null)
    {
        if ($this->_request->getActionName() === $action && (null === $module ||
                $this->_request->getModuleName() === $module) && (null === $controller ||
                $this->_request->getControllerName() === $controller)
        ) {
            return;
        }

        $this->_request->initForward();

        if ($module) {
            $this->_request->setModuleName($module);
        }
        if ($controller) {
            $this->_request->setControllerName($controller);
        }
        $this->_request->setActionName($action)->setDispatched(false);
    }
}
