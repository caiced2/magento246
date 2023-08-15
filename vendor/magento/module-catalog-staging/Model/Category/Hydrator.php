<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model\Category;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Controller\Adminhtml\Category\Save;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as ResourceCategory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Catalog\Model\CategoryFactory;

class Hydrator implements HydratorInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var Save
     */
    protected $originalController;

    /**
     * @param Context $context
     * @param CategoryFactory $categoryFactory
     * @param Save $originalController
     */
    public function __construct(
        Context $context,
        CategoryFactory $categoryFactory,
        Save $originalController
    ) {
        $this->context = $context;
        $this->categoryFactory = $categoryFactory;
        $this->originalController = $originalController;
    }

    /**
     * @inheritDoc
     */
    public function hydrate(array $requestData)
    {
        /** @var Category $category */
        $category = $this->categoryFactory->create();
        $requestData = $this->originalController->stringToBoolConverting($requestData);
        $requestData = $this->originalController->imagePreprocessing($requestData);
        $category->addData($requestData);
        $category->isObjectNew(true);

        $useConfigItems = $this->getUseConfigSettings($requestData);
        $this->processUseConfigSettings($category, $useConfigItems);

        $this->context->getEventManager()->dispatch(
            'catalog_category_prepare_save',
            ['category' => $category, 'request' => $this->context->getRequest()]
        );

        $this->processUseDefaultValues($category, $requestData);

        $category->setData('use_post_data_config', $useConfigItems);
        $this->validate($category);
        $category->unsetData('use_post_data_config');

        return $category;
    }

    /**
     * Category validate process
     *
     * @param Category $category
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function validate(Category $category)
    {
        /** @var ResourceCategory $categoryResource */
        $categoryResource = $category->getResource();
        $validate = $category->validate();
        if ($validate !== true) {
            foreach ($validate as $code => $error) {
                if ($error === true) {
                    $attribute = $categoryResource->getAttribute($code)->getFrontend()->getLabel();
                    throw new LocalizedException(
                        __('The "%1" attribute is required. Enter and try again.', $attribute)
                    );
                } else {
                    throw new LocalizedException($error);
                }
            }
        }
    }

    /**
     * Retrieve use config settigns array
     *
     * @param array $data
     * @return array
     */
    protected function getUseConfigSettings($data)
    {
        $useConfig = [];
        if (isset($data['use_config']) && !empty($data['use_config'])) {
            foreach ($data['use_config'] as $attributeCode => $attributeValue) {
                if ($attributeValue) {
                    $useConfig[] = $attributeCode;
                }
            }
        }
        return $useConfig;
    }

    /**
     * Process "Use Config Settings" checkboxes
     *
     * @param Category $category
     * @param array $useConfigItems
     * @return void
     */
    protected function processUseConfigSettings(Category $category, $useConfigItems)
    {
        foreach ($useConfigItems as $item) {
            $category->setData($item, null);
        }
    }

    /**
     * Check "Use Default Value" checkboxes values
     *
     * @param Category $category
     * @param array $data
     * @return void
     */
    protected function processUseDefaultValues(Category $category, $data)
    {
        if (isset($data['use_default']) && !empty($data['use_default'])) {
            foreach ($data['use_default'] as $attributeCode => $attributeValue) {
                if ($attributeValue) {
                    $category->setData($attributeCode, null);
                }
            }
        }
    }
}
