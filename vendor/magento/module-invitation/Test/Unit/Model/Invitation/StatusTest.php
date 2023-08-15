<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Test\Unit\Model\Invitation;

use Magento\Invitation\Model\Invitation\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /**
     * @param bool $isAdmin
     * @param string[] $statuses
     * @return void
     * @dataProvider dataProviderGetCanBeSentStatuses
     */
    public function testGetCanBeSentStatuses($isAdmin, $statuses)
    {
        $model = new Status($isAdmin);
        $this->assertEquals($statuses, $model->getCanBeSentStatuses());
    }

    /**
     * @return array
     */
    public function dataProviderGetCanBeSentStatuses()
    {
        return [
            [
                false,
                [
                    Status::STATUS_NEW,
                ],
            ],
            [
                true,
                [
                    Status::STATUS_NEW,
                    Status::STATUS_CANCELED,
                    Status::STATUS_SENT,
                ],
            ],
        ];
    }
}
