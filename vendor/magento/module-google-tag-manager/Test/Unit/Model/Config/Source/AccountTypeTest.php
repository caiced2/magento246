<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GoogleTagManager\Helper\Data as Helper;
use Magento\GoogleTagManager\Model\Config\Source\AccountType;
use PHPUnit\Framework\TestCase;

class AccountTypeTest extends TestCase
{
    /** @var AccountType */
    protected $accountType;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->accountType = $this->objectManagerHelper->getObject(
            AccountType::class
        );
    }

    public function testToOptionArray()
    {
        $options =  [
            [
                'value' => Helper::TYPE_UNIVERSAL,
                'label' => __('Universal Analytics')
            ],
            [
                'value' => Helper::TYPE_TAG_MANAGER,
                'label' => __('Google Tag Manager')
            ],
        ];
        $this->assertEquals($options, $this->accountType->toOptionArray());
    }
}
