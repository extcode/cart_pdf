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
use Extcode\CartPdf\Domain\Model\Dto\PdfDemand;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class PdfService
{
    protected array $pluginSettings = [];

    protected array $cartSettings = [];

    protected array $pdfSettings = [];

    protected ?PdfDemand $pdfDemand = null;

    protected ?TcpdfWrapper $pdf = null;

    protected int $border = 1;

    public function __construct(
        protected readonly ConfigurationManager $configurationManager,
        protected readonly OrderItemRepository $orderItemRepository,
        protected readonly PersistenceManager $persistenceManager,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly StorageRepository $storageRepository
    ) {}

    public function createPdf(OrderItem $orderItem, string $pdfType): void
    {
        $this->setPluginSettings($pdfType);

        $pdfFilename = '/tmp/tempfile.pdf';

        $this->renderPdf($orderItem, $pdfType);

        $getNumber = 'get' . ucfirst($pdfType) . 'Number';
        $newFileName = $orderItem->{$getNumber}() . '.pdf';

        if (file_exists($pdfFilename)) {
            $storage = $this->storageRepository->findByUid((int)$this->pdfSettings['storageRepository']);

            if ($storage) {
                $targetFolder = $storage->getFolder((string)$this->pdfSettings['storageFolder']);

                if ($targetFolder) {
                    $falFile = $targetFolder->addFile(
                        $pdfFilename,
                        $newFileName,
                        DuplicationBehavior::RENAME
                    );

                    $falFileReference = $this->createFileReferenceFromFalFileObject($falFile);

                    $addPdfFunction = 'add' . ucfirst($pdfType) . 'Pdf';
                    $orderItem->{$addPdfFunction}($falFileReference);
                }
            }
        }

        $this->orderItemRepository->update($orderItem);
        $this->persistenceManager->persistAll();
    }

    protected function renderPdf(OrderItem $orderItem, string $pdfType): void
    {
        $pluginSettings = $this->configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK,
            'cartpdf'
        );

        $this->pdf = GeneralUtility::makeInstance(TcpdfWrapper::class);
        $this->pdf->setSettings($pluginSettings);
        $this->pdf->setCartPdfType($pdfType . 'Pdf');

        if (empty($this->pdfSettings['header'])) {
            $this->pdf->setPrintHeader(false);
        } else {
            if (!empty($this->pdfSettings['header']['margin'])) {
                $this->pdf->setHeaderMargin((int)$this->pdfSettings['header']['margin']);
                $this->pdf->SetMargins(PDF_MARGIN_LEFT, (float)$this->pdfSettings['header']['margin'], PDF_MARGIN_RIGHT);
            } else {
                $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            }
        }

        if (empty($this->pdfSettings['footer'])) {
            $this->pdf->setPrintFooter(false);
        } else {
            if (!empty($this->pdfSettings['footer']['margin'])) {
                $this->pdf->setFooterMargin((int)$this->pdfSettings['footer']['margin']);
                $this->pdf->setAutoPageBreak(true, (int)$this->pdfSettings['footer']['margin']);
            } else {
                $this->pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            }
        }

        $this->pdf->AddPage();

        $font = 'Helvetica';

        if (!empty($this->pdfSettings['font'])) {
            $font = $this->pdfSettings['font'];
        }

        $fontStyle = '';

        if (!empty($this->pdfSettings['fontStyle'])) {
            $fontStyle = $this->pdfSettings['fontStyle'];
        }

        $fontSize = 8;

        if (!empty($this->pdfSettings['fontSize'])) {
            $fontSize = $this->pdfSettings['fontSize'];
        }

        $this->pdf->SetFont($font, $fontStyle, $fontSize);

        $colorArray = [0, 0, 0];

        if (!empty($this->pdfSettings['drawColor'])) {
            $colorArray = explode(',', $this->pdfSettings['drawColor']);
        }

        $this->pdf->setDrawColorArray($colorArray);
        $this->renderMarker();

        if (is_array($this->pdfSettings['letterhead']['html'] ?? null)) {
            foreach ($this->pdfSettings['letterhead']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . 'Pdf/Letterhead/';
                $assignToView = ['orderItem' => $orderItem];
                $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        if (is_array($this->pdfSettings['body']['before']['html'] ?? null)) {
            foreach ($this->pdfSettings['body']['before']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . 'Pdf/Body/Before/';
                $assignToView = ['orderItem' => $orderItem];
                $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        $this->renderCart($orderItem, $pdfType);

        if (is_array($this->pdfSettings['body']['after']['html'] ?? null)) {
            foreach ($this->pdfSettings['body']['after']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . 'Pdf/Body/After/';
                $assignToView = ['orderItem' => $orderItem];
                $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        $pdfFilename = '/tmp/tempfile.pdf';

        $this->pdf->Output($pdfFilename, 'F');
    }

    protected function renderMarker(): void
    {
        if ($this->pdfDemand->getFoldMarksEnabled()) {
            $this->pdf->SetLineWidth(0.1);
            $this->pdf->Line(6.0, 105.0, 8.0, 105.0);
            $this->pdf->Line(6.0, 148.5, 10.0, 148.5);
            $this->pdf->Line(6.0, 210.0, 8.0, 210.0);
            $this->pdf->SetLineWidth(0.2);
        }

        if ($this->pdfDemand->getAddressFieldMarksEnabled()) {
            $this->pdf->SetLineWidth(0.1);

            $this->pdf->Line(20.0, 45.0, 21.0, 45.0);
            $this->pdf->Line(20.0, 45.0, 20.0, 46.0);
            $this->pdf->Line(105.0, 45.0, 104.0, 45.0);
            $this->pdf->Line(105.0, 45.0, 105.0, 46.0);

            $this->pdf->Line(20.0, 90.0, 21.0, 90.0);
            $this->pdf->Line(20.0, 90.0, 20.0, 89.0);

            $this->pdf->Line(105.0, 90.0, 104.0, 90.0);
            $this->pdf->Line(105.0, 90.0, 105.0, 89.0);

            $this->pdf->SetLineWidth(0.2);
        }
    }

    protected function renderCart(OrderItem $orderItem, string $pdfType): void
    {
        $pdfType .= 'Pdf';

        $config = $this->pdfSettings['body']['order'] ?? [];
        $config['height'] = 0;

        if (empty($config['spacingY']) && empty($config['positionY'])) {
            $config['spacingY'] = 5;
        }

        $headerOut = $this->renderCartHeader($orderItem, $pdfType);
        $bodyOut = $this->renderCartBody($orderItem, $pdfType);
        $footerOut = $this->renderCartFooter($orderItem, $pdfType);

        $content = '<table cellpadding="3">' . $headerOut . $bodyOut . $footerOut . '</table>';

        $this->pdf->writeHtmlCellWithConfig($content, $config);
    }

    protected function renderCartHeader(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->pdf->getStandaloneView('/' . ucfirst($pdfType) . '/Order/', 'Header');
        $view->assign('orderItem', $orderItem);
        $header = $view->render();

        return trim(preg_replace('~[\\n]+~', '', $header));
    }

    protected function renderCartBody(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->pdf->getStandaloneView('/' . ucfirst($pdfType) . '/Order/', 'Product');
        $view->assign('orderItem', $orderItem);

        $bodyOut = '';

        foreach ($orderItem->getProducts() as $product) {
            $view->assign('product', $product);
            $product = $view->render();

            $bodyOut .= trim(preg_replace('~[\\n]+~', '', $product));
        }

        return $bodyOut;
    }

    protected function renderCartFooter(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->pdf->getStandaloneView('/' . ucfirst($pdfType) . '/Order/', 'Footer');
        $view->assign('orderSettings', $this->pdfSettings['body']['order']);
        $view->assign('orderItem', $orderItem);
        $footer = $view->render();

        return trim(preg_replace('~[\\n]+~', '', $footer));
    }

    protected function setPluginSettings(string $pdfType): void
    {
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

        $this->pluginSettings
            = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'CartPdf'
            );

        $this->cartSettings
            = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'Cart'
            );

        $this->pdfSettings = $this->pluginSettings[$pdfType . 'Pdf'];
        $this->pdfDemand = GeneralUtility::makeInstance(PdfDemand::class);

        $this->pdfDemand->setFontSize(
            (int)($this->pdfSettings['fontSize'] ?? 11)
        );

        $this->pdfDemand->setDebug(
            (int)($this->pdfSettings['debug'] ?? 0)
        );

        $this->pdfDemand->setFoldMarksEnabled(
            (bool)($this->pdfSettings['enableFoldMarks'] ?? 0)
        );

        $this->pdfDemand->setAddressFieldMarksEnabled(
            (bool)($this->pdfSettings['enableAddressFieldMarks'] ?? 0)
        );
    }

    protected function createFileReferenceFromFalFileObject(File $file): FileReference
    {
        $falFileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local'   => $file->getUid(),
                'uid_foreign' => StringUtility::getUniqueId('NEW_'),
                'uid'         => StringUtility::getUniqueId('NEW_'),
                'crop'        => null,
            ]
        );

        $fileReference = GeneralUtility::makeInstance(
            FileReference::class
        );

        $fileReference->setOriginalResource($falFileReference);

        return $fileReference;
    }
}
