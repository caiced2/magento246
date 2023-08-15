<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Wrapping;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftWrapping\Model\Wrapping;
use Magento\GiftWrapping\Model\Wrapping\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /** @var Validator */
    protected $validator;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->validator = $this->objectManagerHelper->getObject(Validator::class);
    }

    public function testValidateWithError()
    {
        $wrapping = $this->objectManagerHelper->getObject(Wrapping::class);
        $wrapping->setData('status', 'Status');
        $wrapping->setData('base_price', 'Price');

        $this->assertFalse($this->validator->isValid($wrapping));
    }

    public function testValidateSuccess()
    {
        $wrapping = $this->objectManagerHelper->getObject(Wrapping::class);
        $wrapping->setData('status', 'Status');
        $wrapping->setData('base_price', 'Price');
        $wrapping->setData('design', 'Design');

        $this->assertTrue($this->validator->isValid($wrapping));
    }
}
