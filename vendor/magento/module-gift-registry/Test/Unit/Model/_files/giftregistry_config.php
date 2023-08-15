<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

return [
    'attribute_types' => ['text' => ['label' => 'Text']],
    'attribute_groups' => [
        'event_information' => ['sortOrder' => '5', 'visible' => 'true', 'label' => 'Event Information'],
    ],
    'registry' => [
        'static_attributes' => [
            'event_country' => [
                'type' => 'country',
                'group' => 'event_information',
                'visible' => 'true',
                'label' => 'Event Country',
            ],
        ],
        'custom_attributes' => [
            'my_event_special' => [
                'type' => 'country',
                'group' => 'event_information',
                'visible' => 'true',
                'label' => 'My event special',
            ],
        ],
    ],
    'registrant' => [
        'static_attributes' => [
            'role' => ['type' => 'select', 'group' => 'registrant', 'visible' => 'true', 'label' => 'Role'],
        ],
        'custom_attributes' => [
            'my_special_attribute' => [
                'type' => 'country',
                'group' => 'registrant',
                'visible' => 'true',
                'label' => 'My special attribute',
            ],
        ],
    ]
];
