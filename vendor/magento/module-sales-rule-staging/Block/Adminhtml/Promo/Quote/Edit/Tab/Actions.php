<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Block\Adminhtml\Promo\Quote\Edit\Tab;

/**
 * Actions tab for sales rule update form.
 */
class Actions extends \Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\Tab\Actions
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
     * @param \Magento\Config\Model\Config\Source\Yesno $sourceYesno
     * @param \Magento\Rule\Block\Actions $ruleActions
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Staging\Model\VersionManager $versionManager
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $sourceYesno,
        \Magento\Rule\Block\Actions $ruleActions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Model\VersionManager $versionManager,
        array $data = []
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->updateRepository = $updateRepository;
        $this->versionManager = $versionManager;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $sourceYesno,
            $ruleActions,
            $rendererFieldset,
            $data,
            $ruleFactory
        );
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
        $model->getActions()->setFormName('salesrulestaging_update_form');
        $model->getActions()->setJsFormObject(
            $model->getActionsFieldSetId($model->getActions()->getFormName())
        );
        $form = $this->addTabToForm($model, 'staging_actions_fieldset', $model->getActions()->getFormName());
        $this->setForm($form);
        return $this;
    }
}
