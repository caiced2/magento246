<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleStaging\Test\Unit\Model\Product;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Framework\DataObject;
use Magento\BundleStaging\Model\Product\RetrieverPlugin;
use PHPUnit\Framework\TestCase;

class RetrieverPluginTest extends TestCase
{
    public function testAfterGetEntity()
    {
        $plugin = new RetrieverPlugin();
        $subject = $this->getMockBuilder(RetrieverInterface::class)
            ->getMockForAbstractClass();
        $result = new DataObject(
            [
                'has_options' => '1',
                'required_options' => '1',
                'type_id' => BundleType::TYPE_CODE
            ]
        );

        $modifiedResult = $plugin->afterGetEntity($subject, $result);
        $this->assertTrue($modifiedResult->getCanSaveBundleSelections());
        $this->assertTrue($modifiedResult->getTypeHasOptions());
        $this->assertTrue($modifiedResult->getTypeHasRequiredOptions());
    }
}
