<?php

declare(strict_types=1);

/*
 * Additional products for the order of OrderItem.php, so that all products
 * fit on the first page of the invoice, but the summary does not fit below
 * the last product. This applies with and without the subtotal and the
 * amount brought forward for 17 to 21 additional products, so 19 is in the
 * middle of both ranges.
 */
return [
    'tx_cart_domain_model_order_product' => array_map(
        static fn(int $uid): array => [
            'uid' => $uid,
            'pid' => 1,
            'item' => 1,
            'product_type' => 'simple',
            'sku' => sprintf('BULK-%03d', $uid),
            'title' => sprintf('Massenartikel Nr. %03d', $uid),
            'count' => 1,
            'price' => 1.00,
            'gross' => 1.00,
            'net' => 0.84,
            'tax' => 0.16,
            'tax_class' => 1,
        ],
        range(3, 21)
    ),
];
