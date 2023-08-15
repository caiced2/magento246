<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

return [
    'callbacks' => ['group_one' => ['class_one' => 'method_one', 'class_two' => 'method_two']],
    'acl' => ['level_one' => ['rule_one' => 'resource_one', 'rule_two' => 'resource_two']],
    'processors' => ['group_one' => 'processor_class']
];
