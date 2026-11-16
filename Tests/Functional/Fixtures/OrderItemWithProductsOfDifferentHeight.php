<?php

declare(strict_types=1);

/*
 * Additional products for the order of OrderItem.php, so that the product
 * list does not fit on one page. From product 30 on, every third product has
 * a title that wraps over several lines, so the number of products per page
 * differs from page to page.
 *
 * The gross price equals the uid, so every subtotal is unique.
 */
return [
    'tx_cart_domain_model_order_product' => array_map(
        static fn(int $uid): array => [
            'uid' => $uid,
            'pid' => 1,
            'item' => 1,
            'product_type' => 'simple',
            'sku' => sprintf('BULK-%03d', $uid),
            'title' => $uid >= 30 && $uid % 3 === 0
                ? sprintf('Massenartikel Nr. %03d', $uid) . str_repeat(' mit einer sehr langen Beschreibung', 6)
                : sprintf('Massenartikel Nr. %03d', $uid),
            'count' => 1,
            'price' => (float)$uid,
            'gross' => (float)$uid,
            'net' => round($uid / 1.19, 2),
            'tax' => round($uid - $uid / 1.19, 2),
            'tax_class' => 1,
        ],
        range(3, 80)
    ),
];
