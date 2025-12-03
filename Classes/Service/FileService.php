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
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

readonly class FileService implements FileServiceInterface
{
    public function __construct(
        private StorageRepository $storageRepository,
    ) {}

    public function getFolder(array $configuration, OrderItem $orderItem, string $pdfType): ?Folder
    {
        $storage = $this->storageRepository->findByUid((int)$configuration['storageRepository']);
        if (($storage instanceof ResourceStorage) === false) {
            return null;
        }

        $folder = $storage->getFolder((string)$configuration['storageFolder']);
        if ($folder instanceof InaccessibleFolder) {
            return null;
        }

        return $folder;
    }

    public function getFilename(array $configuration, OrderItem $orderItem, string $pdfType): string
    {
        $getNumber = 'get' . ucfirst($pdfType) . 'Number';

        return $orderItem->{$getNumber}() . '.pdf';
    }
}
