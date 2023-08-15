<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Controller\Adminhtml\Category\Update;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Staging\Model\Entity\Update\Save as StagingUpdateSave;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Staging save class.
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Entity request identifier.
     */
    const ENTITY_IDENTIFIER = 'entity_id';

    /**
     * Entity name.
     */
    const ENTITY_NAME = 'catalog_category';

    /**
     * @var StagingUpdateSave
     */
    protected $stagingUpdateSave;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Action\Context $context
     * @param StagingUpdateSave $stagingUpdateSave
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        StagingUpdateSave $stagingUpdateSave,
        StoreManagerInterface $storeManager
    ) {
        $this->stagingUpdateSave = $stagingUpdateSave;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Checking if admin has permissions to save staging.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Staging::staging')
        && $this->_authorization->isAllowed('Magento_Catalog::catalog');
    }

    /**
     * Performing staging save.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $categoryPostData = $this->getRequest()->getPostValue();

        $storeId = isset($categoryPostData['store_id']) ? $categoryPostData['store_id'] : null;
        $store = $this->storeManager->getStore($storeId);
        $this->storeManager->setCurrentStore($store->getCode());

        return $this->stagingUpdateSave->execute(
            [
                'entityId' => $this->getRequest()->getParam(static::ENTITY_IDENTIFIER),
                'stagingData' => $this->getRequest()->getParam('staging'),
                'entityData' => $categoryPostData
            ]
        );
    }
}
