<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog layered navigation view block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\LayeredNavigationStaging\Block;

use Magento\Framework\View\Element\Template;
use Magento\Staging\Model\VersionManager;

class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Model\Layer\FilterList $filterList
     * @param \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag
     * @param VersionManager $versionManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\Layer\FilterList $filterList,
        \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag,
        VersionManager $versionManager,
        array $data = []
    ) {
        $this->versionManager = $versionManager;
        parent::__construct($context, $layerResolver, $filterList, $visibilityFlag, $data);
    }

    /**
     * Check availability display layer block
     *
     * @return bool
     */
    public function canShowBlock()
    {
        return parent::canShowBlock() && !$this->versionManager->isPreviewVersion();
    }
}
