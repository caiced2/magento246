<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Adminhtml\System\Config\Source;

use Magento\CatalogPermissions\Model\Adminhtml\System\Config\Source\Grant;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CatalogPermissions\Model\Adminhtml\System\Config\Source\Grant
 */
class GrantTest extends TestCase
{
    /**
     * @var Grant
     */
    protected $grant;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->grant = new Grant();
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        foreach ($this->grant->toOptionArray() as $item) {
            $this->assertTrue($item instanceof Phrase || is_string($item));
        }
    }
}
