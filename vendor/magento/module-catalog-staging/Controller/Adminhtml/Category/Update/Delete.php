<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Controller\Adminhtml\Category\Update;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Staging\Model\Entity\Update\Delete as StagingUpdateDelete;
use Magento\Staging\Model\VersionManager;

/**
 * Staging deletion class.
 */
class Delete extends Action implements HttpPostActionInterface
{
    /**
     * Entity request identifier.
     */
    const ENTITY_IDENTIFIER = 'id';

    /**
     * Entity name.
     */
    const ENTITY_NAME = 'catalog_category';

    /**
     * @var StagingUpdateDelete
     */
    protected $stagingUpdateDelete;

    /**
     * @param Action\Context $context
     * @param StagingUpdateDelete $stagingUpdateDelete
     */
    public function __construct(
        Action\Context $context,
        StagingUpdateDelete $stagingUpdateDelete
    ) {
        $this->stagingUpdateDelete = $stagingUpdateDelete;
        parent::__construct($context);
    }

    /**
     * Checking if admin has permissions to delete staging.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Staging::staging')
        && $this->_authorization->isAllowed('Magento_Catalog::catalog');
    }

    /**
     * Performing stating deletion.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $mode = $this->getRequest()->getParam('staging')['mode'];
        if ($mode === 'remove') {
            $this->addVersionToRequest();
        }

        return $this->stagingUpdateDelete->execute(
            [
                'entityId' => $this->getRequest()->getParam(static::ENTITY_IDENTIFIER),
                'updateId' => $this->getRequest()->getParam('update_id'),
                'stagingData' => $this->getRequest()->getParam('staging')
            ]
        );
    }

    /**
     * Add update version to request.
     *
     * Add update version to request params to prevent deleting all catalog category sequence when
     * a scheduled update is currently active.
     *
     * @return void
     */
    private function addVersionToRequest()
    {
        $requestParams = $this->getRequest()->getParams();

        $requestParams[VersionManager::PARAM_NAME]
            = $this->getRequest()->getParam('update_id');

        $this->getRequest()->setParams($requestParams);
    }
}
