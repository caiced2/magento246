<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Controller\Adminhtml\Page\Update;

use Magento\Backend\App\Action;
use Magento\Staging\Model\Entity\Update\Delete as StagingUpdateDelete;

class Delete extends Action
{
    /**
     * Entity request identifier
     */
    const ENTITY_IDENTIFIER = 'page_id';

    /**
     * Entity name
     */
    const ENTITY_NAME = 'page';

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
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Staging::staging')
        && $this->_authorization->isAllowed('Magento_Cms::save');
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->stagingUpdateDelete->execute(
            [
                'entityId' => $this->getRequest()->getParam(static::ENTITY_IDENTIFIER),
                'updateId' => $this->getRequest()->getParam('update_id'),
                'stagingData' => $this->getRequest()->getParam('staging')
            ]
        );
    }
}
