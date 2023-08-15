<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Ui\Component\Form;

use Magento\Ui\Component\AbstractComponent;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class Fieldset
 */
class Fieldset extends AbstractComponent
{
    /**
     * Component name
     */
    const NAME = 'fieldset';

    /**
     * @param ContextInterface $context
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
    }

    /**
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        return static::NAME;
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $updateId = $this->getContext()->getRequestParam('id');
        $config = $this->getData('config');

        if (!$updateId) {
            $config['componentDisabled'] = true;
        }
        $this->setData('config', (array)$config);
        parent::prepare();
    }
}
