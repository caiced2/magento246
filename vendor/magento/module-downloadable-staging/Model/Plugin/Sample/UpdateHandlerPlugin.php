<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DownloadableStaging\Model\Plugin\Sample;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Downloadable\Api\Data\SampleInterface;
use Magento\Downloadable\Model\Sample\UpdateHandler;
use Magento\Downloadable\Model\SampleFactory;
use Magento\Downloadable\Model\Product\Type;
use Magento\Downloadable\Model\ResourceModel\Sample as SampleResource;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\VersionManager;

/**
 * Update Handler plugin for Downloadable Product Samples.
 */
class UpdateHandlerPlugin
{
    /**
     * @var SampleFactory
     */
    private $sampleFactory;

    /**
     * @var SampleResource
     */
    private $sampleResource;

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
     * @param SampleFactory $sampleFactory
     * @param SampleResource $sampleResource
     * @param Type $downloadableType
     * @param MetadataPool $metadataPool
     * @param VersionManager $versionManager
     */
    public function __construct(
        SampleFactory $sampleFactory,
        SampleResource $sampleResource,
        Type $downloadableType,
        MetadataPool $metadataPool,
        VersionManager $versionManager
    ) {
        $this->sampleFactory = $sampleFactory;
        $this->sampleResource = $sampleResource;
        $this->downloadableType = $downloadableType;
        $this->metadataPool = $metadataPool;
        $this->versionManager = $versionManager;
    }

    /**
     * Update intersected rollbacks with new Downloadable Samples data.
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
        $samples = $extensionAttributes->getDownloadableProductSamples() ?? [];

        if ($samples
            && isset($arguments[$linkField], $arguments['created_in'])
            && $arguments['created_in'] === $this->versionManager->getCurrentVersion()->getId()
            && $entity->getTypeId() === Type::TYPE_DOWNLOADABLE
        ) {
            $intersectedRollbacks = $this->getSamplesIds($entity);
            $samplesToUpdate = array_filter($samples, function ($sample) {
                return (bool) $sample->getId();
            });

            if (count($intersectedRollbacks) >= count($samplesToUpdate)) {
                foreach ($samplesToUpdate as $sample) {
                    $this->updateSampleVersion(
                        $sample,
                        (int) $arguments[$linkField],
                        array_shift($intersectedRollbacks)
                    );
                }
            }

            $extensionAttributes->setDownloadableProductSamples($samples);
        }

        return [$entity, $arguments];
    }

    /**
     * Retrieve Downloadable Samples ids from the provided entity.
     *
     * @param ProductInterface $entity
     * @return int[]
     */
    private function getSamplesIds(ProductInterface $entity): array
    {
        $samplesIds = [];
        $entity->unsDownloadableSamples();
        $samples = $this->downloadableType->getSamples($entity);

        foreach ($samples as $sample) {
            $samplesIds[] = (int) $sample->getId();
        }

        return $samplesIds;
    }

    /**
     * Update Downloadable Sample ID with the provided one.
     *
     * @param SampleInterface $sample
     * @param int $productId
     * @param int $replacementId
     * @return void
     */
    private function updateSampleVersion(SampleInterface $sample, int $productId, int $replacementId): void
    {
        $existingSample = $this->sampleFactory->create();
        $this->sampleResource->load($existingSample, $sample->getId());
        $sampleProductId = (int) $existingSample->getProductId();

        if ($sampleProductId && $sampleProductId !== $productId) {
            $sample->setId($replacementId);
        }
    }
}
