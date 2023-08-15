<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Preview;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Staging\Model\VersionManager;

/**
 * Class RequireInjector
 *
 * @api
 * @since 100.1.0
 */
class RequireInjector extends Template
{
    const INJECTIONS_LIST = 'requireInjectionsList';
    const MODULE_NAME = 'requireModuleName';

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param Context $context
     * @param VersionManager $versionManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        VersionManager $versionManager,
        array $data = []
    ) {
        $this->versionManager = $versionManager;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     * @since 100.1.0
     */
    protected function _toHtml()
    {
        if ($this->versionManager->isPreviewVersion()
            && $this->getInjectionsList() != null
            && $this->getRequireModuleName() != null
        ) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Get module name
     *
     * @return string|null
     * @since 100.1.0
     */
    public function getRequireModuleName()
    {
        return $this->getData(self::MODULE_NAME);
    }

    /**
     * Get injections list
     *
     * @return array|null
     * @since 100.1.0
     */
    public function getInjectionsList()
    {
        return $this->getData(self::INJECTIONS_LIST);
    }
}
