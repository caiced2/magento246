<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Invitation status history model
 *
 * @method int getInvitationId()
 * @method \Magento\Invitation\Model\Invitation\History setInvitationId(int $value)
 * @method string getInvitationDate()
 * @method \Magento\Invitation\Model\Invitation\History setInvitationDate(string $value)
 * @method string getStatus()
 * @method \Magento\Invitation\Model\Invitation\History setStatus(string $value)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Invitation\Model\Invitation;

class History extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Invitation Status
     *
     * @var \Magento\Invitation\Model\Source\Invitation\Status
     */
    protected $_invitationStatus;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Invitation\Model\Source\Invitation\Status $invitationStatus
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Invitation\Model\Source\Invitation\Status $invitationStatus,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_invitationStatus = $invitationStatus;
        $this->dateTime = $dateTime;
        $this->_init(\Magento\Invitation\Model\ResourceModel\Invitation\History::class);
    }

    /**
     * Return status text
     *
     * @return string
     */
    public function getStatusText()
    {
        return $this->_invitationStatus->getOptionText($this->getStatus());
    }

    /**
     * Set additional data before saving
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->setInvitationDate($this->dateTime->formatDate(time()));
        return parent::beforeSave();
    }
}
