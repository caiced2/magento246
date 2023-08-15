<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardSampleData\Setup;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\WidgetSampleData\Model\CmsBlock;

class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * Setup class for category
     *
     * @var \Magento\CatalogSampleData\Model\Category
     */
    protected $categorySetup;

    /**
     * Setup class for product attributes
     *
     * @var \Magento\CatalogSampleData\Model\Attribute
     */
    protected $attributeSetup;

    /**
     * @var \Magento\ProductLinksSampleData\Model\ProductLink
     */
    protected $productLinkSetup;

    /**
     * @var \Magento\GiftCardSampleData\Model\Product
     */
    protected $product;

    /**
     * Setup class for css
     *
     * @var \Magento\ThemeSampleData\Model\Css
     */
    private $css;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CmsBlock
     */
    private $widgetCmsBlockSetup;

    /**
     * @var \Magento\CmsSampleData\Model\Block|null
     */
    private $cmsBlockSetup;

    /**
     * @param \Magento\CatalogSampleData\Model\Category $categorySetup
     * @param \Magento\CatalogSampleData\Model\Attribute $attributeSetup
     * @param \Magento\GiftCardSampleData\Model\Product $product
     * @param \Magento\ProductLinksSampleData\Model\ProductLink $productLinkSetup
     * @param \Magento\ThemeSampleData\Model\Css $css
     * @param StoreManagerInterface $storeManager
     * @param \Magento\CmsSampleData\Model\Block|null $cmsBlockSetup
     * @param CmsBlock|null $widgetCmsBlockSetup
     */
    public function __construct(
        \Magento\CatalogSampleData\Model\Category $categorySetup,
        \Magento\CatalogSampleData\Model\Attribute $attributeSetup,
        \Magento\GiftCardSampleData\Model\Product $product,
        \Magento\ProductLinksSampleData\Model\ProductLink $productLinkSetup,
        \Magento\ThemeSampleData\Model\Css $css,
        StoreManagerInterface $storeManager = null,
        \Magento\CmsSampleData\Model\Block $cmsBlockSetup = null,
        CmsBlock $widgetCmsBlockSetup = null
    ) {
        $this->product = $product;
        $this->attributeSetup = $attributeSetup;
        $this->categorySetup = $categorySetup;
        $this->productLinkSetup = $productLinkSetup;
        $this->css = $css;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->cmsBlockSetup = $cmsBlockSetup ?: ObjectManager::getInstance()->get(\Magento\CmsSampleData\Model\Block::class);
        $this->widgetCmsBlockSetup = $widgetCmsBlockSetup ?: ObjectManager::getInstance()->get(CmsBlock::class);
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->storeManager->setCurrentStore(Store::DISTRO_STORE_ID);
        $this->attributeSetup->install(['Magento_GiftCardSampleData::fixtures/attributes.csv']);
        $this->categorySetup->install(['Magento_GiftCardSampleData::fixtures/categories.csv']);
        $this->product->install(
            ['Magento_GiftCardSampleData::fixtures/products_giftcard.csv'],
            ['Magento_GiftCardSampleData::fixtures/images_giftcard.csv']
        );
        $this->productLinkSetup->install(
            ['Magento_GiftCardSampleData::fixtures/related.csv'],
            [],
            ['Magento_GiftCardSampleData::fixtures/crossell.csv']
        );
        $this->cmsBlockSetup->install(
            [
                'Magento_GiftCardSampleData::fixtures/categories_static_blocks_giftcard.csv',
            ]
        );
        $this->widgetCmsBlockSetup->install(
            [
                'Magento_GiftCardSampleData::fixtures/cmsblock_giftcard.csv'
            ]
        );
        $this->css->install(['Magento_GiftCardSampleData::fixtures/styles.css' => 'styles.css']);
    }
}
