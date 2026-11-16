<?php

declare(strict_types=1);

return [
    'be_users' => [
        ['uid' => 1, 'pid' => 0, 'username' => 'admin', 'admin' => 1],
    ],
    'sys_file_storage' => [
        [
            'uid' => 1,
            'pid' => 0,
            'name' => 'fileadmin',
            'driver' => 'Local',
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="basePath"><value index="vDEF">fileadmin/</value></field><field index="pathType"><value index="vDEF">relative</value></field><field index="caseSensitive"><value index="vDEF">1</value></field></language></sheet></data></T3FlexForms>',
            'is_browsable' => 1,
            'is_public' => 1,
            'is_writable' => 1,
            'is_online' => 1,
        ],
    ],
];
