<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogImportExport\Model;

use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Option\Repository;
use Magento\Catalog\Model\Product\Option\SaveHandler;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\ObjectManagerInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class responsible for creating schedule update for products.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Version
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create update for product
     *
     * @param array $skus
     * @param AbstractProductExportImportTestCase $testInstance
     * @return void
     * @throws \Exception
     */
    public function create(array $skus, AbstractProductExportImportTestCase $testInstance = null): void
    {
        $date = new \DateTime();
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $updateRepository = $this->objectManager->get(UpdateRepositoryInterface::class);
        $versionManager = $this->objectManager->get(VersionManager::class);
        $productStaging = $this->objectManager->get(ProductStagingInterface::class);
        $hydratorPool = $this->objectManager->get(HydratorPool::class);
        $hydrator = $hydratorPool->getHydrator(UpdateInterface::class);

        $i = 2;
        foreach ($skus as $sku) {
            $startDate = $date->add(new \DateInterval('P' . $i . 'D'))->format('Y-m-d H:i:s');

            $stagingData = [
                'mode'        => 'save',
                'update_id'   => null,
                'name'        => 'New update ' . $startDate,
                'description' => 'New update',
                'start_time'  => $startDate,
                'end_time'    => null,
                'select_id'   => null,
            ];

            $update = $this->objectManager->create(UpdateInterface::class);
            $hydrator->hydrate($update, $stagingData);
            $update->setIsCampaign(false);
            $update->setId(strtotime($update->getStartTime()));
            $update->isObjectNew(true);
            $updateRepository->save($update);

            $product = $productRepository->get($sku);
            $oldVersion = $versionManager->getCurrentVersion();
            $versionManager->setCurrentVersionId($update->getId());
            $this->prepareCustomOptions($product);
            if ($testInstance) {
                $testInstance->prepareProduct($product);
            }
            $product->setName('My Product ' . $startDate);
            $productStaging->schedule($product, $update->getId());
            $versionManager->setCurrentVersionId($oldVersion->getId());

            $i++;
        }
    }

    /**
     * Prepare custom options
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function prepareCustomOptions($product)
    {
        $this->objectManager->removeSharedInstance(ProductRepository::class);
        $this->objectManager->removeSharedInstance(Repository::class);
        $this->objectManager->removeSharedInstance(SaveHandler::class);

        if ($product->getOptions()) {
            $customOptionFactory = $this->objectManager->get(ProductCustomOptionInterfaceFactory::class);

            $options = [];
            foreach ($product->getOptions() as $option) {
                $optionData = $option->getData();
                unset(
                    $optionData['id'],
                    $optionData['option_id'],
                    $optionData['product_id']
                );
                $optionValues = [];
                if ($option->getValues()) {
                    foreach ($option->getValues() as $optionValue) {
                        $optionValueData = $optionValue->getData();
                        unset(
                            $optionValueData['option_type_id'],
                            $optionValueData['option_id']
                        );
                        $optionValues[] = $optionValueData;
                    }
                    $optionData['values'] = $optionValues;
                }
                $option = $customOptionFactory->create(['data' => $optionData]);
                $option->setProductSku($product->getSku());
                $options[] = $option;
            }
            $product->setOptions($options);
        }
    }
}
