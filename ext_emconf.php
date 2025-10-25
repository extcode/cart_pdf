<?php

declare(strict_types=1);

$EM_CONF['cart_pdf'] = [
    'title'            => 'Cart - PDF',
    'description'      => 'Shopping Cart(s) for TYPO3 - PDF Renderer for Invoices and Delivery Notes',
    'category'         => 'services',
    'author'           => 'Daniel Gohlke',
    'author_email'     => 'ext.cart@extco.de',
    'author_company'   => 'extco.de UG (haftungsbeschränkt)',
    'shy'              => '',
    'priority'         => '',
    'module'           => '',
    'state'            => 'beta',
    'internal'         => '',
    'uploadfolder'     => '0',
    'createDirs'       => '',
    'modify_tables'    => '',
    'clearCacheOnLoad' => 0,
    'lockType'         => '',
    'version'          => '5.0.0',
    'constraints'      => [
        'depends' => [
            'typo3' => '13.4.0-13.4.999',
            'cart'  => '11.5',
            'tcpdf' => '6.7.0-6.99.999',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
