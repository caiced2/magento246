<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index extends \Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy implements HttpGetActionInterface
{
    /**
     * Load layout, set active menu and breadcrumbs
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_VersionsCms::versionscms_page_hierarchy'
        )->_addBreadcrumb(
            __('CMS'),
            __('CMS')
        )->_addBreadcrumb(
            __('CMS Page Trees'),
            __('CMS Page Trees')
        );
        return $this;
    }

    /**
     * Show Tree Edit Page
     *
     * @return void
     */
    public function execute()
    {
        $this->_initScope();

        $nodeModel = $this->_objectManager->create(
            \Magento\VersionsCms\Model\Hierarchy\Node::class,
            ['data' => ['scope' => $this->_scope, 'scope_id' => $this->_scopeId]]
        );

        // restore data if exists
        $formData = $this->_getSession()->getFormData(true);
        if (!empty($formData)) {
            $nodeModel->addData($formData);
            unset($formData);
        }

        $this->_coreRegistry->register('current_hierarchy_node', $nodeModel);

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Hierarchy'));
        $this->_view->renderLayout();
    }
}
