<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Staging\Model\Entity\Update\Delete as StagingUpdateDelete;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;

class Delete extends Action
{
    /**
     * Entity request identifier
     */
    const ENTITY_IDENTIFIER = 'entity_id';

    /**
     * Entity name
     */
    const ENTITY_NAME = 'product';

    /**
     * @var StagingUpdateDelete
     */
    protected $stagingUpdateDelete;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Action\Context $context
     * @param StagingUpdateDelete $stagingUpdateDelete
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        StagingUpdateDelete $stagingUpdateDelete,
        StoreManagerInterface $storeManager
    ) {
        $this->stagingUpdateDelete = $stagingUpdateDelete;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Staging::staging')
        && $this->_authorization->isAllowed('Magento_Catalog::products');
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (isset($data['product']['current_store_id'])) {
            $selectedStore = $this->storeManager->getStore((int)$data['product']['current_store_id']);
            $this->storeManager->setCurrentStore($selectedStore);
        }
        return $this->stagingUpdateDelete->execute(
            [
                'entityId' => $this->getRequest()->getParam(static::ENTITY_IDENTIFIER),
                'updateId' => $this->getRequest()->getParam('update_id'),
                'stagingData' => $this->getRequest()->getParam('staging')
            ]
        );
    }
}
