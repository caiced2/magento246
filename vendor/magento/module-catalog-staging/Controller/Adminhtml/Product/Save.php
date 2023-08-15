<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Staging\Model\Entity\Update\Save as StagingUpdateSave;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Save replace product save controller for update creation
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Action
{
    /**
     * Entity request identifier
     */
    const ENTITY_IDENTIFIER = 'id';

    /**
     * Entity name
     */
    const ENTITY_NAME = 'catalog_product';

    /**
     * @var StagingUpdateSave
     */
    protected $stagingUpdateSave;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Staging::staging')
        && $this->_authorization->isAllowed('Magento_Catalog::products');
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (isset($data['product']['current_store_id'])) {
            $selectedStore = $this->storeManager->getStore((int)$data['product']['current_store_id']);
            $this->storeManager->setCurrentStore($selectedStore);
        }
        return $this->stagingUpdateSave->execute(
            [
                'entityId' => $this->getRequest()->getParam(static::ENTITY_IDENTIFIER),
                'stagingData' => $this->getRequest()->getParam('staging'),
                'entityData' => $data

            ]
        );
    }
}
