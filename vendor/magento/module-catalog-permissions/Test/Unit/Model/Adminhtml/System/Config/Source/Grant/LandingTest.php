<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Adminhtml\System\Config\Source\Grant;

use Magento\CatalogPermissions\Model\Adminhtml\System\Config\Source\Grant\Landing;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CatalogPermissions\Model\Adminhtml\System\Config\Source\Grant\Landing
 */
class LandingTest extends TestCase
{
    /**
     * @var Landing
     */
    protected $landing;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->landing = new Landing();
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        foreach ($this->landing->toOptionArray() as $item) {
            $this->assertTrue($item instanceof Phrase || is_string($item));
        }
    }
}
