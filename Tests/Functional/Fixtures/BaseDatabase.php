<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

return [
    'pages' => [
        [
            'uid' => 1,
            'pid' => 0,
            'title' => 'Root',
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'slug' => '/',
            'is_siteroot' => 1,
        ],
    ],
    'sys_template' => [
        [
            'uid' => 1,
            'pid' => 1,
            'title' => 'Root',
            'root' => 1,
            'clear' => 3,
            'include_static_file' => implode(',', [
                'EXT:cart/Configuration/TypoScript',
                'EXT:cart_pdf/Configuration/TypoScript',
                'EXT:cart_pdf/Configuration/TypoScript/InvoicePdf',
            ]),
        ],
    ],
];
