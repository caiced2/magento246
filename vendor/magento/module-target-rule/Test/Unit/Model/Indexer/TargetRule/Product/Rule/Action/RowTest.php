<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Product\Rule\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row;
use PHPUnit\Framework\TestCase;

class RowTest extends TestCase
{
    /**
     * @var Row
     */
    protected $_model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->_model = $objectManager->getObject(
            Row::class
        );
    }

    public function testEmptyId()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('We can\'t rebuild the index for an undefined product.');
        $this->_model->execute(null);
    }
}
