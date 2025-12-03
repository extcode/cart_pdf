<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Service;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Domain\Model\Order\Item as OrderItem;
use TYPO3\CMS\Core\Resource\Folder;

interface FileServiceInterface
{
    public function getFolder(array $configuration, OrderItem $orderItem, string $pdfType): ?Folder;

    public function getFilename(array $configuration, OrderItem $orderItem, string $pdfType): string;
}
