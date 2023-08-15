<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Url;

use Magento\Staging\Model\VersionManager;
use Magento\UrlRewrite\Model\UrlPersistInterface;

/**
 * Class DbStorage
 */
class Storage implements UrlPersistInterface
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var UrlPersistInterface
     */
    private $urlPersist;

    /**
     * DbStorage constructor.
     *
     * @param UrlPersistInterface $urlPersist
     * @param VersionManager $versionManager
     */
    public function __construct(
        UrlPersistInterface $urlPersist,
        VersionManager $versionManager
    ) {
        $this->urlPersist = $urlPersist;
        $this->versionManager = $versionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByData(array $data)
    {
        if (!$this->versionManager->isPreviewVersion()) {
            $this->urlPersist->deleteByData($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $urls)
    {
        if (!$this->versionManager->isPreviewVersion()) {
            return $this->urlPersist->replace($urls);
        }
        return [];
    }
}
