<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Block\Adminhtml\Promo\Quote\Edit\Tab;

/**
 * Conditions tab for sales rule update form.
 */
class Conditions extends \Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab\Conditions
{
    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    protected $versionManager;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    private $ruleFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Rule\Block\Conditions $conditions
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
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
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Model\VersionManager $versionManager,
        array $data = []
    ) {
        $this->updateRepository = $updateRepository;
        $this->versionManager = $versionManager;
        $this->ruleFactory = $ruleFactory;
        parent::__construct($context, $registry, $formFactory, $conditions, $rendererFieldset, $data, $ruleFactory);
    }

    /**
     * {@inheritdoc}
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

        $model = $this->ruleFactory->create();
        $model->load($entityId);
        $model->getConditions()->setFormName('salesrulestaging_update_form');
        $model->getConditions()->setJsFormObject(
            $model->getConditionsFieldSetId($model->getConditions()->getFormName())
        );
        $form = $this->addTabToForm($model, 'staging_conditions_fieldset', $model->getConditions()->getFormName());
        $this->setForm($form);
        return $this;
    }
}
