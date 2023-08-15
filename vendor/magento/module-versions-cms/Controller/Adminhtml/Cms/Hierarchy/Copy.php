<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy;

class Copy extends \Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy
{
    /**
     * Copy hierarchy from one scope to other scopes
     *
     * @return void
     */
    public function execute()
    {
        $this->_initScope();
        $scopes = $this->getRequest()->getParam('scopes');
        if ($this->getRequest()->isPost() && is_array($scopes) && !empty($scopes)) {
            /** @var $nodeModel \Magento\VersionsCms\Model\Hierarchy\Node */
            $nodeModel = $this->_objectManager->create(
                \Magento\VersionsCms\Model\Hierarchy\Node::class,
                ['data' => ['scope' => $this->_scope, 'scope_id' => $this->_scopeId]]
            );
            $nodeHeritageModel = $nodeModel->getHeritage();
            try {
                foreach (array_unique($scopes) as $value) {
                    list($scope, $scopeId) = $this->_getScopeData($value);
                    $nodeHeritageModel->copyTo($scope, $scopeId);
                }
                $this->messageManager->addSuccess(__('You copied the pages hierarchy to the selected scopes.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while copying the hierarchy.'));
            }
        }

        $this->_redirect('adminhtml/*/index', ['website' => $this->_website, 'store' => $this->_store]);
        return;
    }
}
