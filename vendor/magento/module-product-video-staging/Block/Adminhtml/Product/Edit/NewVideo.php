<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ProductVideoStaging\Block\Adminhtml\Product\Edit;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\ProductVideo\Helper\Media;
use Magento\Framework\Json\EncoderInterface;

class NewVideo extends \Magento\ProductVideo\Block\Adminhtml\Product\Edit\NewVideo
{
    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var string
     */
    protected $videoSelector = '#staging_media_gallery_content';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Media $mediaHelper
     * @param EncoderInterface $jsonEncoder
     * @param LocatorInterface $locator
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Media $mediaHelper,
        EncoderInterface $jsonEncoder,
        LocatorInterface $locator,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $mediaHelper, $jsonEncoder, $data);
        $this->locator = $locator;
    }

    /**
     * Retrieve currently viewed product object
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function getProduct()
    {
        return $this->locator->getProduct();
    }
}
