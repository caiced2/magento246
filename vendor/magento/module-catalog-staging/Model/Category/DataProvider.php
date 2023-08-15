<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Category;

use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\DataProvider\MetadataProvider;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\EavValidationRules;
use Magento\Catalog\Model\CategoryFactory;
use Psr\Log\LoggerInterface;

/**
 * DataProvider for category staging form
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends CategoryDataProvider
{
    /**
     * {@inheritdoc}
     */
    protected $ignoreFields = [
        'products_position'
    ];

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param EavValidationRules $eavValidationRules
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param Config $eavConfig
     * @param \Magento\Framework\App\RequestInterface $request
     * @param CategoryFactory $categoryFactory
     * @param MetadataProvider $metadataProvider
     * @param array $meta
     * @param array $data
     * @param UpdateRepositoryInterface|null $updateRepository
     * @param VersionManager|null $versionManager
     * @param LoggerInterface|null $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        EavValidationRules $eavValidationRules,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        Config $eavConfig,
        \Magento\Framework\App\RequestInterface $request,
        CategoryFactory $categoryFactory,
        MetadataProvider $metadataProvider,
        array $meta = [],
        array $data = [],
        UpdateRepositoryInterface $updateRepository = null,
        VersionManager $versionManager = null,
        LoggerInterface $logger = null
    ) {
        $meta = array_replace_recursive($meta, $metadataProvider->getMetadata());
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $eavValidationRules,
            $categoryCollectionFactory,
            $storeManager,
            $registry,
            $eavConfig,
            $request,
            $categoryFactory,
            $meta,
            $data
        );
        $objectManager = ObjectManager::getInstance();
        $this->updateRepository = $updateRepository ?? $objectManager->get(UpdateRepositoryInterface::class);
        $this->versionManager = $versionManager ?? $objectManager->get(VersionManager::class);
        $this->logger = $logger ?? $objectManager->get(LoggerInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentCategory()
    {
        $updateId = (int) $this->request->getParam('update_id');
        try {
            if ($updateId) {
                $update = $this->updateRepository->get($updateId);
                $this->versionManager->setCurrentVersionId($update->getId());
            }
        } catch (NoSuchEntityException $exception) {
            $this->logger->error($exception);
        }
        return parent::getCurrentCategory();
    }
}
