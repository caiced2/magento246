<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

return [
    'without_required_action_handle' => [
        '<?xml version="1.0"?><config></config>',
        ["Element 'config': Missing child element(s). Expected is ( action ).\nLine: 1\n"],
    ],
    'action_with_same_paths_value' => [
        '<?xml version="1.0"?><config><action path="some_path_name" type="register"/>' .
        '<action path="some_path_name" type="register"/></config>',
        [
            "Element 'action': Duplicate key-sequence ['some_path_name'] in unique " .
            "identity-constraint 'uniqueActionPath'.\nLine: 1\n"
        ],
    ],
    'action_with_notallowed_attribute' => [
        '<?xml version="1.0"?><config><action path="some_path_name" type="register" notallowed="test"/></config>',
        ["Element 'action', attribute 'notallowed': The attribute 'notallowed' is not allowed.\nLine: 1\n"],
    ],
    'action_without_required_path_attribute' => [
        '<?xml version="1.0"?><config><action type="register" /></config>',
        ["Element 'action': The attribute 'path' is required but missing.\nLine: 1\n"],
    ],
    'action_without_required_type_attribute' => [
        '<?xml version="1.0"?><config><action path="some_path_name" /></config>',
        ["Element 'action': The attribute 'type' is required but missing.\nLine: 1\n"],
    ],
    'action_path_invalid_value' => [
        '<?xml version="1.0"?><config><action path="1234" type="register" /></config>',
        [
            "Element 'action', attribute 'path': [facet 'pattern'] The value '1234' is not accepted by the " .
            "pattern '[a-zA-Z_]+'.\nLine: 1\n"
        ],
    ],
    'action_path_empty_value' => [
        '<?xml version="1.0"?><config><action path="" type="register" /></config>',
        [
            "Element 'action', attribute 'path': [facet 'pattern'] The value '' is not accepted by the " .
            "pattern '[a-zA-Z_]+'.\nLine: 1\n"
        ],
    ],
    'action_type_invalid_value' => [
        '<?xml version="1.0"?><config><action path="some_path_name" type="invalidvalue" /></config>',
        [
            "Element 'action', attribute 'type': [facet 'enumeration'] The value 'invalidvalue' is not an " .
            "element of the set {'register', 'generic'}.\nLine: 1\n"
        ],
    ]
];
