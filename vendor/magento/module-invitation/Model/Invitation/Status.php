<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Model\Invitation;

class Status
{
    const STATUS_NEW = 'new';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_CANCELED = 'canceled';

    /**
     * @var bool
     */
    protected $isAdmin = false;

    /**
     * @param bool $isAdmin
     */
    public function __construct($isAdmin = false)
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * Return invitation statuses that can be sent
     *
     * @return string[]
     */
    public function getCanBeSentStatuses()
    {
        $statuses = [
            self::STATUS_NEW,
        ];
        if ($this->isAdmin) {
            $statuses[] = self::STATUS_CANCELED;
            $statuses[] = self::STATUS_SENT;
        }
        return $statuses;
    }

    /**
     * Return invitation statuses that can be accepted
     *
     * @return string[]
     */
    public function getCanBeAcceptedStatuses()
    {
        return [
            self::STATUS_NEW,
            self::STATUS_SENT,
        ];
    }

    /**
     * Return invitation statuses that can be cancelled
     *
     * @return string[]
     */
    public function getCanBeCancelledStatuses()
    {
        return [
            self::STATUS_NEW,
            self::STATUS_SENT,
        ];
    }
}
