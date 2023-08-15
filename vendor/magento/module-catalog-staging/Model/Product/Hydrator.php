<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Controller\Adminhtml\Product\Builder as ProductBuilder;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Model\Product\TypeTransitionManager;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\Entity\PersisterInterface;
use Magento\CatalogStaging\Model\Product\Retriever;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Hydrator implements HydratorInterface
{
    /**
     * @var Helper
     */
    protected $initializationHelper;

    /**
     * @var ProductBuilder
     */
    protected $productBuilder;

    /**
     * @var TypeTransitionManager
     */
    protected $productTypeManager;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Retriever
     */
    protected $entityRetriever;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Helper $initializationHelper
     * @param ProductBuilder $productBuilder
     * @param TypeTransitionManager $productTypeManager
     * @param VersionManager $versionManager
     * @param UpdateRepositoryInterface $updateRepository
     * @param Retriever $entityRetriever
     * @param StoreManagerInterface $storeManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Helper $initializationHelper,
        ProductBuilder $productBuilder,
        TypeTransitionManager $productTypeManager,
        VersionManager $versionManager,
        UpdateRepositoryInterface $updateRepository,
        Retriever $entityRetriever
    ) {
        $this->context = $context;
        $this->initializationHelper = $initializationHelper;
        $this->productBuilder = $productBuilder;
        $this->productTypeManager = $productTypeManager;
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->entityRetriever = $entityRetriever;
    }

    /**
     * @inheritDoc
     */
    public function hydrate(array $data)
    {
        $product = $this->initializationHelper->initialize(
            $this->productBuilder->build($this->context->getRequest())
        );
        $this->productTypeManager->processProduct($product);

        if (isset($data['product'][$product->getIdFieldName()])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The product was unable to be saved. Please try again.')
            );
        }

        $startTime = null;
        $endTime = null;
        if ($data['product']['is_new']) {
            $currentVersionId = $this->versionManager->getCurrentVersion()->getId();
            $update = $this->updateRepository->get($currentVersionId);
            $startTime = $update->getStartTime();
            $endTime = $update->getEndTime();
        }
        $product->setNewsFromDate($startTime);
        $product->setNewsToDate($endTime);
        $this->handleImageRemoveError($data, $product->getId());
        return $product;
    }

    /**
     * Notify customer when image was not deleted in specific case.
     * TODO: temporary workaround must be eliminated in MAGETWO-45306
     *
     * @param array $postData
     * @param int $productId
     * @return void
     */
    private function handleImageRemoveError($postData, $productId)
    {
        if (isset($postData['product']['media_gallery']['images'])) {
            $removedImagesAmount = 0;
            foreach ($postData['product']['media_gallery']['images'] as $image) {
                if (!empty($image['removed'])) {
                    $removedImagesAmount++;
                }
            }
            if ($removedImagesAmount) {
                $expectedImagesAmount = count($postData['product']['media_gallery']['images']) - $removedImagesAmount;

                $product = $this->entityRetriever->getEntity($productId);
                if ($expectedImagesAmount != count($product->getMediaGallery('images'))) {
                    $this->context->getMessageManager()->addNotice(
                        __('The image cannot be removed as it has been assigned to the other image role')
                    );
                }
            }
        }
    }
}
