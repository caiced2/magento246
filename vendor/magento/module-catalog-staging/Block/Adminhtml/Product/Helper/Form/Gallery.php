<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Block\Adminhtml\Product\Helper\Form;

use Magento\Framework\Registry;
use Magento\Framework\Data\Form;
use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery as CatalogGallery;
use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Context;

class Gallery extends CatalogGallery
{
    /**
     * @var string
     */
    protected $fieldNameSuffix = 'product';

    /**
     * @var string
     */
    protected $htmlId = 'staging_media_gallery';

    /**
     * @var string
     */
    protected $name = 'product[media_gallery]';

    /**
     * @var string
     */
    protected $image = 'image';

    /**
     * @var StagingLocator
     */
    private $locator;

    /**
     * @var string
     */
    protected $formName = 'catalogstaging_update_form';

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param Form $form
     * @param StagingLocator $locator
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Registry $registry,
        Form $form,
        StagingLocator $locator,
        $data = []
    ) {
        parent::__construct($context, $storeManager, $registry, $form, $data);
        $this->locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages()
    {
        return $this->locator->getProduct()->getData('media_gallery') ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataObject()
    {
        return $this->locator->getProduct();
    }
}
