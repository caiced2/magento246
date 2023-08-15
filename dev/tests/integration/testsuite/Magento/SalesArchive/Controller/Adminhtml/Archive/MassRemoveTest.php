<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Controller\Adminhtml\Archive;

/**
 * Checks mass remove orders from archive
 *
 * @see \Magento\SalesArchive\Controller\Adminhtml\Archive\MassRemove
 *
 * @magentoAppArea adminhtml
 */
class MassRemoveTest extends AbstractMassActionTest
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource = 'Magento_SalesArchive::remove';
        $this->uri = 'backend/sales/archive/massremove';
        parent::setUp();
    }

    /**
     * @magentoConfigFixture default_store sales/magento_salesarchive/active 1
     * @magentoDataFixture Magento/SalesArchive/_files/archived_pending_order.php
     * @return void
     */
    public function testMassReturnToOrderManagement(): void
    {
        $this->prepareRequest(['100000001']);
        $this->dispatch($this->uri);
        $this->assertSessionMessages(
            $this->containsEqual((string)__('We removed %1 order(s) from the archive.', 1))
        );
    }
}
