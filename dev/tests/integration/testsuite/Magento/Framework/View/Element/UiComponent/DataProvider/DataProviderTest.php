<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\View\Element\UiComponent\DataProvider;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Request;
use Magento\Staging\Model\VersionManager;

/**
 * Represents DataProviderTest class
 *
 * @magentoDbIsolation enabled
 * @magentoAppArea adminhtml
 */
class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\View\Element\UiComponent\DataProvider\Reporting
     */
    private $reporting;

    /**
     * @var \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\TestFramework\Request
     */
    private $request;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var int
     */
    private $currentVersionId;

    /**
     * @var \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
     */
    private $dataProvider;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->request = $objectManager->get(Request::class);
        $this->request->setParams(['id' => '1']);
        $this->versionManager = $objectManager->get(VersionManager::class);
        $this->currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        $this->versionManager->setCurrentVersionId(101);
        $this->collectionFactory = $objectManager->create(CollectionFactory::class);

        $this->reporting = $objectManager->create(
            Reporting::class,
            ['collectionFactory' => $this->collectionFactory,
                'request' => $this->request]
        );
        $this->dataProvider = $objectManager->create(
            DataProvider::class,
            ['name' => 'catalogstaging_upcoming_grid_data_source',
                'primaryFieldName' => 'row_id',
                'requestFieldName' => 'id',
            ]
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->versionManager->setCurrentVersionId($this->currentVersionId);
    }

    /**
     * Tests the search method on data provider
     *
     * @magentoDataFixture Magento/Staging/_files/staging_catalog_product_entity.php
     * @magentoDataFixture Magento/Staging/_files/staging_update.php
     */
    public function testSearch()
    {
        $result = $this->dataProvider->getSearchResult();
        // staging_catalog_product_entity created 4 updates
        $this->assertCount(4, $result->getItems());
    }
}
