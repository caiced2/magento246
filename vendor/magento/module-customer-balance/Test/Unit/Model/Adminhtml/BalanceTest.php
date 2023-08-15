<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Model\Adminhtml;

use Magento\CustomerBalance\Model\Adminhtml\Balance;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test \Magento\CustomerBalance\Model\Adminhtml\Balance
 */
class BalanceTest extends TestCase
{
    /**
     * @var Balance
     */
    protected $_model;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        /** @var Balance $model */
        $this->_model = $helper->getObject(Balance::class);
    }

    public function testGetWebsiteIdWithException()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string)__('Please set a website ID.'));
        $this->_model->getWebsiteId();
    }

    public function testGetWebsiteId()
    {
        $this->_model->setWebsiteId('some id');
        $this->assertEquals('some id', $this->_model->getWebsiteId());
    }
}
