<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model;

use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class Update extends AbstractExtensibleModel implements UpdateInterface
{
    /**
     * Identifier for update item
     *
     * @var string
     */
    protected $entityType = 'update';

    /**
     * @var string
     */
    protected $_eventPrefix = 'staging_update';

    /**
     * @var string
     */
    protected $_eventObject = 'update';

    /**
     * Initialize update resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Staging\Model\ResourceModel\Update::class);
    }

    /**
     * Retrieve update id
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(UpdateInterface::ID);
    }

    /**
     * Retrieve update start datetime
     *
     * @return string
     */
    public function getStartTime()
    {
        return $this->getData(UpdateInterface::START_TIME);
    }

    /**
     * Retrieve update name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getData(UpdateInterface::NAME);
    }

    /**
     * Retrieve update description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getData(UpdateInterface::DESCRIPTION);
    }

    /**
     * Retrieve update rollback id
     *
     * @return int
     */
    public function getRollbackId()
    {
        return $this->getData(UpdateInterface::ROLLBACK_ID);
    }

    /**
     * Check if update is a update
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsCampaign()
    {
        return $this->getData(UpdateInterface::IS_CAMPAIGN);
    }

    /**
     * Check if update is a rollback
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsRollback()
    {
        return $this->getData(UpdateInterface::IS_ROLLBACK);
    }

    /**
     * @return string
     */
    public function getEndTime()
    {
        return $this->getData(UpdateInterface::END_TIME);
    }

    /**
     * Retrieve update id to which current update should be moved
     *
     * @return int|null
     */
    public function getMovedTo()
    {
        return $this->getData(UpdateInterface::MOVED_TO);
    }

    /**
     * Retrieve update id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(UpdateInterface::ID, $id);
    }

    /**
     * Set update start datetime
     *
     * @param string $startTimestamp
     * @return $this
     */
    public function setStartTime($startTimestamp)
    {
        return $this->setData(UpdateInterface::START_TIME, $startTimestamp);
    }

    /**
     * Set update name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(UpdateInterface::NAME, $name);
    }

    /**
     * Set update description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData(UpdateInterface::DESCRIPTION, $description);
    }

    /**
     * Set next update id
     *
     * @param int $id
     * @return $this
     */
    public function setRollbackId($id)
    {
        return $this->setData(UpdateInterface::ROLLBACK_ID, $id);
    }

    /**
     * Claim that update is a update
     *
     * @param string $isCampaign
     * @return $this
     */
    public function setIsCampaign($isCampaign)
    {
        return $this->setData(UpdateInterface::IS_CAMPAIGN, $isCampaign);
    }

    /**
     * Claim that update is a rollback
     *
     * @param bool $isRollback
     * @return $this
     */
    public function setIsRollback($isRollback)
    {
        return $this->setData(UpdateInterface::IS_ROLLBACK, $isRollback);
    }

    /**
     * Set update end time
     *
     * @param string $time
     * @return $this
     */
    public function setEndTime($time)
    {
        return $this->setData(UpdateInterface::END_TIME, $time);
    }

    /**
     * Set new update id to which current update should be moved
     *
     * @param int $id
     * @return $this
     */
    public function setMovedTo($id)
    {
        return $this->setData(UpdateInterface::MOVED_TO, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensionAttributes(\Magento\Staging\Api\Data\UpdateExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
