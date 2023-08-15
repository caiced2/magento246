<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer\PlaceOrder\Restriction;

use Magento\Reward\Helper\Data;
use Magento\Reward\Observer\PlaceOrder\Restriction\Frontend;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FrontendTest extends TestCase
{
    /**
     * @var Frontend
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_helper;

    protected function setUp(): void
    {
        $this->_helper = $this->createMock(Data::class);
        $this->_model = new Frontend($this->_helper);
    }

    public function testIsAllowed()
    {
        $this->_helper->expects($this->once())->method('isEnabledOnFront');
        $this->_model->isAllowed();
    }
}
