<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Observer;

use Magento\AdminGws\Model\Models;
use Magento\AdminGws\Observer\CatalogProductValidateAfter;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CatalogProductValidateAfterTest extends TestCase
{
    /**
     * @var CatalogProductValidateAfter
     */
    private $catalogProductValidateAfterObserver;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var Models
     */
    private $models;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->models = $this->getMockBuilder(
            Models::class
        )->setMethods(
            ['catalogProductValidateAfter']
        )->disableOriginalConstructor()
            ->getMock();

        $this->observer = $this->getMockBuilder(
            Observer::class
        )->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->catalogProductValidateAfterObserver = $objectManagerHelper->getObject(
            CatalogProductValidateAfter::class,
            [
                'models' => $this->models,
            ]
        );
    }

    /**
     * @return void
     */
    public function testUpdateRoleStores()
    {
        $this->models->expects($this->atLeastOnce())->method('catalogProductValidateAfter');
        $this->catalogProductValidateAfterObserver->execute($this->observer);
    }
}
