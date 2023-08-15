<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DownloadableStaging\Model\Plugin\Link;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Downloadable\Api\Data\LinkInterface;
use Magento\Downloadable\Model\Link\UpdateHandler;
use Magento\Downloadable\Model\LinkFactory;
use Magento\Downloadable\Model\Product\Type;
use Magento\Downloadable\Model\ResourceModel\Link as LinkResource;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\VersionManager;

/**
 * Update Handler plugin for Downloadable Product Links.
 */
class UpdateHandlerPlugin
{
    /**
     * @var LinkFactory
     */
    private $linkFactory;

    /**
     * @var LinkResource
     */
    private $linkResource;

    /**
     * @var Type
     */
    private $downloadableType;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param LinkFactory $linkFactory
     * @param LinkResource $linkResource
     * @param Type $downloadableType
     * @param MetadataPool $metadataPool
     * @param VersionManager $versionManager
     */
    public function __construct(
        LinkFactory $linkFactory,
        LinkResource $linkResource,
        Type $downloadableType,
        MetadataPool $metadataPool,
        VersionManager $versionManager
    ) {
        $this->linkFactory = $linkFactory;
        $this->linkResource = $linkResource;
        $this->downloadableType = $downloadableType;
        $this->metadataPool = $metadataPool;
        $this->versionManager = $versionManager;
    }

    /**
     * Update intersected rollbacks with new Downloadable Links data.
     *
     * @param UpdateHandler $subject
     * @param ProductInterface $entity
     * @param array $arguments
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        UpdateHandler $subject,
        ProductInterface $entity,
        array $arguments = []
    ): array {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $extensionAttributes = $entity->getExtensionAttributes();
        $links = $extensionAttributes->getDownloadableProductLinks() ?? [];

        if ($links
            && isset($arguments[$linkField], $arguments['created_in'])
            && $arguments['created_in'] === $this->versionManager->getCurrentVersion()->getId()
            && $entity->getTypeId() === Type::TYPE_DOWNLOADABLE
        ) {
            $intersectedRollbacks = $this->getLinksIds($entity);
            $linksToUpdate = array_filter($links, function ($link) {
                return (bool) $link->getId();
            });

            if (count($intersectedRollbacks) >= count($linksToUpdate)) {
                foreach ($linksToUpdate as $link) {
                    $this->updateLinkVersion(
                        $link,
                        (int) $arguments[$linkField],
                        array_shift($intersectedRollbacks)
                    );
                }
            }

            $extensionAttributes->setDownloadableProductLinks($links);
        }

        return [$entity, $arguments];
    }

    /**
     * Retrieve Downloadable Links ids from the provided entity.
     *
     * @param ProductInterface $entity
     * @return int[]
     */
    private function getLinksIds(ProductInterface $entity): array
    {
        $linksIds = [];
        $entity->unsDownloadableLinks();
        $links = $this->downloadableType->getLinks($entity);

        foreach ($links as $link) {
            $linksIds[] = (int) $link->getId();
        }

        return $linksIds;
    }

    /**
     * Update Downloadable Link ID with the provided one.
     *
     * @param LinkInterface $link
     * @param int $productId
     * @param int $replacementId
     */
    private function updateLinkVersion(LinkInterface $link, int $productId, int $replacementId): void
    {
        $existingLink = $this->linkFactory->create();
        $this->linkResource->load($existingLink, $link->getId());
        $linkProductId = (int) $existingLink->getProductId();

        if ($linkProductId && $linkProductId !== $productId) {
            $link->setId($replacementId);
        }
    }
}
