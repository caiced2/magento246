<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\ResourceModel\Rma\Status;

use Magento\Framework\Model\AbstractModel;
use Magento\Rma\Model\Spi\CommentResourceInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\ObjectManager;

/**
 * RMA entity resource model
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class History extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb implements CommentResourceInterface
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param Context $context
     * @param string|null $connectionName
     * @param DateTime|null $dateTime
     */
    public function __construct(
        Context $context,
        $connectionName = null,
        DateTime $dateTime = null
    ) {
        parent::__construct($context, $connectionName);
        $this->dateTime = $dateTime ?: ObjectManager::getInstance()->get(DateTime::class);
    }

    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_rma_status_history', 'entity_id');
    }

    /**
     * @inheritdoc
     */
    protected function _beforeSave(AbstractModel $object)
    {
        parent::_beforeSave($object);
        if (!$object->getCreatedAt()) {
            $object->setCreatedAt($this->dateTime->gmtDate());
        }
        return $this;
    }
}
