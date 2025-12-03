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
use Extcode\Cart\Domain\Repository\Order\ItemRepository as OrderItemRepository;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

readonly class FileWriterService implements FileWriterServiceInterface
{
    private array $configuration;

    public function __construct(
        private ConfigurationManager $configurationManager,
        private OrderItemRepository $orderItemRepository,
        private PersistenceManager $persistenceManager,
        private ResourceFactory $resourceFactory,
        private FileServiceInterface $fileService,
    ) {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $pageId = (int)($GLOBALS['TYPO3_REQUEST']->getQueryParams()['id'] ?? 1);

            $frameworkConfiguration = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
            );
            $persistenceConfiguration = ['persistence' => ['storagePid' => $pageId]];
            $this->configurationManager->setConfiguration(
                array_merge($frameworkConfiguration, $persistenceConfiguration)
            );
        }

        $this->configuration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'CartPdf'
        );
    }

    public function writeContentToFile(OrderItem $orderItem, string $pdfType, string $fileContent): void
    {
        $targetFolder = $this->fileService->getFolder($this->configuration[$pdfType . 'Pdf'], $orderItem, $pdfType);
        if (is_null($targetFolder)) {
            return;
        }

        $file = $targetFolder->createFile(
            uniqid('PdfServiceTempFile_') . '.pdf'
        );
        $file->setContents($fileContent);
        $file->rename(
            $this->fileService->getFilename($this->configuration[$pdfType . 'Pdf'], $orderItem, $pdfType)
        );

        $addPdfFunction = 'add' . ucfirst($pdfType) . 'Pdf';
        $orderItem->{$addPdfFunction}(
            $this->createFileReferenceFromFalFileObject($file)
        );

        $this->orderItemRepository->update($orderItem);
        $this->persistenceManager->persistAll();
    }

    private function createFileReferenceFromFalFileObject(File $file): FileReference
    {
        $falFileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => StringUtility::getUniqueId('NEW_'),
                'uid' => StringUtility::getUniqueId('NEW_'),
                'crop' => null,
            ]
        );

        $fileReference = GeneralUtility::makeInstance(
            FileReference::class
        );

        $fileReference->setOriginalResource($falFileReference);

        return $fileReference;
    }
}
