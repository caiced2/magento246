<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy;

class Delete extends \Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy
{
    /**
     * Delete hierarchy from one or several scopes
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $this->_initScope();
        $scopes = $this->getRequest()->getParam('scopes');
        if (empty($scopes) || $this->getRequest()->isPost() && !is_array(
            $scopes
        ) || $this->getRequest()->isGet() && !is_string(
            $scopes
        )
        ) {
            $this->messageManager->addError(__('Please correct the scope.'));
        } else {
            if (!is_array($scopes)) {
                $scopes = [$scopes];
            }
            try {
                /* @var $nodeModel \Magento\VersionsCms\Model\Hierarchy\Node */
                $nodeModel = $this->_objectManager->create(\Magento\VersionsCms\Model\Hierarchy\Node::class);
                foreach (array_unique($scopes) as $value) {
                    list($scope, $scopeId) = $this->_getScopeData($value);
                    $nodeModel->setScope($scope);
                    $nodeModel->setScopeId($scopeId);
                    $nodeModel->deleteByScope($scope, $scopeId);
                    $nodeModel->collectTree([], []);
                }
                $this->messageManager->addSuccess(__('You deleted the pages hierarchy from the selected scopes.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while deleting the hierarchy.'));
            }
        }

        $this->_redirect('adminhtml/*/index', ['website' => $this->_website, 'store' => $this->_store]);
        return;
    }
}
