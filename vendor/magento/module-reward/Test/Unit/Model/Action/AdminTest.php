<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Model\Action\Admin;
use PHPUnit\Framework\TestCase;

class AdminTest extends TestCase
{
    /**
     * @var Admin
     */
    protected $model;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(Admin::class);
    }

    public function testCanAddRewardPoints()
    {
        $this->assertTrue($this->model->canAddRewardPoints());
    }

    public function testGetHistoryMessage()
    {
        $this->assertEquals('Updated by moderator', $this->model->getHistoryMessage());
    }
}
