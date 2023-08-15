<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCmsUrlRewrite\Plugin\Cms\Model;

use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\CmsUrlRewrite\Model\CmsPageUrlRewriteGenerator;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\PageRepository;

/**
 * Generate and delete url rewrites for root hierarchy of the page
 */
class PageRepositoryPlugin
{
    /**
     * @var UrlPersistInterface
     */
    private $urlPersist;

    /**
     * @var Hierarchy
     */
    private $cmsHierarchy;

    /**
     * @param UrlPersistInterface $urlPersist
     * @param Hierarchy $cmsHierarchy
     */
    public function __construct(
        UrlPersistInterface $urlPersist,
        Hierarchy $cmsHierarchy
    ) {
        $this->urlPersist = $urlPersist;
        $this->cmsHierarchy = $cmsHierarchy;
    }

    /**
     * Flag to generate url rewrites for the page if root hierarchy was selected
     *
     * @param PageRepository $subject
     * @param PageInterface $page
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        PageRepository $subject,
        PageInterface $page
    ) {
        if (!$this->cmsHierarchy->isEnabled()) {
            return;
        }
        if ($page->dataHasChangedFor('assign_to_root')
            && $page->getData('assign_to_root') === 'true'
        ) {
            $page->setData('rewrites_update_force', true);
        }
    }

    /**
     * Delete url rewrites if root hierarchy is unselected for the page
     *
     * @param PageRepository $subject
     * @param PageInterface $page
     * @return PageInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        PageRepository $subject,
        PageInterface $page
    ) {
        if (!$this->cmsHierarchy->isEnabled()) {
            return $page;
        }
        if ($page->hasData('website_root') && !$page->getData('website_root')) {
            $this->urlPersist->deleteByData(
                [
                    UrlRewrite::ENTITY_ID => $page->getId(),
                    UrlRewrite::REQUEST_PATH => $page->getIdentifier(),
                    UrlRewrite::ENTITY_TYPE => CmsPageUrlRewriteGenerator::ENTITY_TYPE,
                ]
            );
        }
        return $page;
    }
}
