<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Adminhtml\Reward;

use Magento\Framework\App\ResponseInterface;

/**
 * Reward admin rate controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Rate extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Reward::rates';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @codeCoverageIgnore
     */
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry)
    {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Check if module functionality enabled
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (!$this->_objectManager->get(
            \Magento\Reward\Helper\Data::class
        )->isEnabled() && $request->getActionName() != 'noroute'
        ) {
            $this->_forward('noroute');
        }
        return parent::dispatch($request);
    }

    /**
     * Initialize layout, breadcrumbs
     *
     * @return $this
     * @codeCoverageIgnore
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_Reward::customer_reward'
        )->_addBreadcrumb(
            __('Customers'),
            __('Customers')
        )->_addBreadcrumb(
            __('Manage Reward Exchange Rates'),
            __('Manage Reward Exchange Rates')
        );
        return $this;
    }

    /**
     * Initialize rate object
     *
     * @return \Magento\Reward\Model\Reward\Rate
     */
    protected function _initRate()
    {
        $rateId = $this->getRequest()->getParam('rate_id', 0);
        $rate = $this->_objectManager->create(\Magento\Reward\Model\Reward\Rate::class);
        if ($rateId) {
            $rate->load($rateId);
        }
        $this->_coreRegistry->register('current_reward_rate', $rate);
        return $rate;
    }
}
