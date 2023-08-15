<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Preview;

use Magento\Staging\Model\VersionManager;

/**
 * Link stub for staging preview
 *
 * @api
 * @since 100.1.0
 */
class LinkStub extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param VersionManager $versionManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        VersionManager $versionManager,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->versionManager = $versionManager;
    }

    /**
     * @return string
     * @since 100.1.0
     */
    protected function _toHtml()
    {
        if ($this->versionManager->isPreviewVersion()) {
            return '';
        }
        return parent::_toHtml();
    }
}
