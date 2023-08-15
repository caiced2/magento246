<?php
/**
 * Reader class for logging.xml
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Model\Config;

class Reader extends \Magento\Framework\Config\Reader\Filesystem
{
    /**
     * List of identifier attributes for merging
     *
     * @var array
     */
    protected $_idAttributes = [
        '/logging/actions/action' => 'id',
        '/logging/groups/group' => 'name',
        '/logging/groups/group/events/event' => 'controller_action',
        '/logging/groups/group/events/event/expected_models/expected_model' => 'class',
        '/logging/groups/group/events/event/expected_models/expected_model/additional_fields/field' => 'name',
        '/logging/groups/group/events/event/expected_models/expected_model/skip_fields/field' => 'name',
        '/logging/groups/group/events/event/skip_on_back/controller_action' => 'name',
        '/logging/groups/group/expected_models/expected_model' => 'class',
        '/logging/groups/group/expected_models/expected_model/additional_fields/field' => 'name',
        '/logging/groups/group/expected_models/expected_model/skip_fields/field' => 'name',
    ];

    /**
     * @param \Magento\Framework\Config\FileResolverInterface $fileResolver
     * @param \Magento\Logging\Model\Config\Converter $converter
     * @param \Magento\Logging\Model\Config\SchemaLocator $schemaLocator
     * @param \Magento\Framework\Config\ValidationStateInterface $validationState
     * @param string $fileName
     * @param array $idAttributes
     * @param string $domDocumentClass
     * @param string $defaultScope
     */
    public function __construct(
        \Magento\Framework\Config\FileResolverInterface $fileResolver,
        \Magento\Logging\Model\Config\Converter $converter,
        \Magento\Logging\Model\Config\SchemaLocator $schemaLocator,
        \Magento\Framework\Config\ValidationStateInterface $validationState,
        $fileName = 'logging.xml',
        $idAttributes = [],
        $domDocumentClass = \Magento\Framework\Config\Dom::class,
        $defaultScope = 'global'
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }
}
