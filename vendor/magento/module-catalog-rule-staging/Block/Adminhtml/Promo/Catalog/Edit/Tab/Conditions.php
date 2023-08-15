<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogRuleStaging\Block\Adminhtml\Promo\Catalog\Edit\Tab;

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;

class Conditions extends \Magento\CatalogRule\Block\Adminhtml\Promo\Catalog\Edit\Tab\Conditions
{
    /**
     * @var CatalogRuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    protected $versionManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Rule\Block\Conditions $conditions
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param CatalogRuleRepositoryInterface $ruleRepository
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Staging\Model\VersionManager $versionManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        CatalogRuleRepositoryInterface $ruleRepository,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Model\VersionManager $versionManager,
        array $data = []
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->updateRepository = $updateRepository;
        $this->versionManager = $versionManager;
        parent::__construct($context, $registry, $formFactory, $conditions, $rendererFieldset, $data);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $entityId = (int)$this->getRequest()->getParam('id');
        $updateId = (int)$this->getRequest()->getParam('update_id');

        try {
            $update = $this->updateRepository->get($updateId);
            $this->versionManager->setCurrentVersionId($update->getId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        }

        $model = $this->ruleRepository->get($entityId);
        $form = $this->addTabToForm($model, 'staging_conditions_fieldset', 'catalogrulestaging_update_form');
        $this->setForm($form);
        return \Magento\Backend\Block\Widget\Form::_prepareForm();
    }
}
