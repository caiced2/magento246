<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Model\ResourceModel;

use Magento\Banner\Test\Fixture\Banner as BannerFixture;
use Magento\CatalogRule\Test\Fixture\Rule as CatalogRuleFixture;
use Magento\CatalogRuleStaging\Test\Fixture\StagedRule as StagedRuleFixture;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Banner\Model\BannerFactory;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Test\Fixture\Update as StagingUpdateFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Model\GroupManagement;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class BannerTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Banner
     */
    private $resourceModel;

    /**
     * @var int
     */
    private $websiteId = 1;

    /**
     * @var BannerFactory
     */
    private $bannerFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var int
     */
    private $customerGroupId = GroupManagement::NOT_LOGGED_IN_ID;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var int
     */
    private $currentVersionId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->resourceModel = $this->objectManager->get(Banner::class);
        $this->bannerFactory = $this->objectManager->get(BannerFactory::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->ruleRepository = $this->objectManager->get(RuleRepositoryInterface::class);
        $this->versionManager = $this->objectManager->get(VersionManager::class);
        $this->currentVersionId = (int) $this->versionManager->getCurrentVersion()->getId();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->versionManager->setCurrentVersionId($this->currentVersionId);
        $this->resourceModel = null;
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/CatalogRule/_files/catalog_rule_10_off_not_logged.php
     * @magentoDataFixture Magento/Banner/_files/banner.php
     * @magentoDbIsolation disabled
     */
    public function testGetCatalogRuleRelatedBannerIdsNoBannerConnected(): void
    {
        $this->assertEmpty(
            $this->resourceModel->getCatalogRuleRelatedBannerIds($this->websiteId, $this->customerGroupId)
        );
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Banner/_files/banner_catalog_rule.php
     * @magentoDbIsolation disabled
     */
    public function testGetCatalogRuleRelatedBannerIds(): void
    {
        $banner = $this->bannerFactory->create();
        $this->resourceModel->load($banner, 'Test Dynamic Block', 'name');

        $this->assertSame(
            [$banner->getId()],
            $this->resourceModel->getCatalogRuleRelatedBannerIds($this->websiteId, $this->customerGroupId)
        );
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Banner/_files/banner_catalog_rule.php
     * @dataProvider getCatalogRuleRelatedBannerIdsWrongDataDataProvider
     * @magentoDbIsolation disabled
     */
    public function testGetCatalogRuleRelatedBannerIdsWrongData($websiteId, $customerGroupId): void
    {
        $this->assertEmpty($this->resourceModel->getCatalogRuleRelatedBannerIds($websiteId, $customerGroupId));
    }

    /**
     * @return array
     */
    public function getCatalogRuleRelatedBannerIdsWrongDataDataProvider(): array
    {
        return [
            'wrong website' => [$this->websiteId + 1, $this->customerGroupId],
            'wrong customer group' => [$this->websiteId, $this->customerGroupId + 1]
        ];
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Banner/_files/banner_disabled_40_percent_off.php
     * @magentoDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     * @magentoDbIsolation disabled
     */
    public function testGetSalesRuleRelatedBannerIds(): void
    {
        $ruleId = $this->registry->registry('Magento/SalesRule/_files/cart_rule_40_percent_off');
        /** @var \Magento\Banner\Model\Banner $banner */
        $banner = $this->bannerFactory->create();
        $this->resourceModel->load($banner, 'Get from 40% to 50% Off on Large Orders', 'name');

        $this->assertEquals(
            [$banner->getId()],
            $this->resourceModel->getSalesRuleRelatedBannerIds([$ruleId])
        );
    }

    /**
     * Get sales rule related banner ids with non active sales rule
     *
     * @return void
     * @magentoDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     * @magentoDbIsolation disabled
     */
    public function testGetSalesRuleRelatedBannerIdsWithNonActiveRule(): void
    {
        $ruleId = $this->registry->registry('Magento/SalesRule/_files/cart_rule_40_percent_off');
        /** @var RuleInterface $rule */
        $rule = $this->ruleRepository->getById($ruleId);
        $rule->setIsActive(0);
        $this->ruleRepository->save($rule);

        $this->assertEmpty($this->resourceModel->getSalesRuleRelatedBannerIds([$ruleId]));
    }

    /**
     * @return void
     * @magentoDataFixture Magento/Banner/_files/banner_enabled_40_to_50_percent_off.php
     * @magentoDataFixture Magento/Banner/_files/banner_disabled_40_percent_off.php
     * @magentoDbIsolation disabled
     */
    public function testGetSalesRuleRelatedBannerIdsNoRules(): void
    {
        $this->assertEmpty($this->resourceModel->getSalesRuleRelatedBannerIds([]));
    }

    #[
        DataFixture(CatalogRuleFixture::class, ['is_active' => false], 'rule1'),
        DataFixture(
            StagingUpdateFixture::class,
            ['start_time' => '+1 day midnight', 'end_time' => '+1 day 11:59:00 pm'],
            'update1'
        ),
        DataFixture(
            StagedRuleFixture::class,
            ['rule_id' => '$rule1.id$', 'update_id' => '$update1.id$', 'is_active' => true],
            'srule1'
        ),
        DataFixture(CatalogRuleFixture::class, ['is_active' => false], 'rule2'),
        DataFixture(
            StagingUpdateFixture::class,
            ['start_time' => '+2 day midnight', 'end_time' => '+2 day 11:59:00 pm'],
            'update2'
        ),
        DataFixture(
            StagedRuleFixture::class,
            ['rule_id' => '$rule2.id$', 'update_id' => '$update2.id$', 'is_active' => true],
            'srule2'
        ),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$rule1.id$']], 'banner1'),
        DataFixture(BannerFixture::class, ['banner_catalog_rules' => ['$rule2.id$']], 'banner2'),
    ]
    public function testStagedCatalogRulesWithBanner(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $banner1 = $fixtures->get('banner1');
        $banner2 = $fixtures->get('banner2');
        $staging1 = $fixtures->get('update1');
        $staging2 = $fixtures->get('update2');

        $this->versionManager->setCurrentVersionId($staging1->getId());
        $this->assertEquals(
            [$banner1->getId()],
            $this->resourceModel->getCatalogRuleRelatedBannerIds($this->websiteId, $this->customerGroupId)
        );

        $this->versionManager->setCurrentVersionId($staging2->getId());
        $this->assertEquals(
            [$banner2->getId()],
            $this->resourceModel->getCatalogRuleRelatedBannerIds($this->websiteId, $this->customerGroupId)
        );
    }
}
