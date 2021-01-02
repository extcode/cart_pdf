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
use Extcode\TCPDF\Service\TsTCPDF;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class PdfService
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var StorageRepository
     */
    protected $storageRepository;

    /**
     * @var array
     */
    protected $pluginSettings = [];

    /**
     * @var array
     */
    protected $cartSettings = [];

    /**
     * @var array
     */
    protected $pdfSettings = [];

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var PdfDemand
     */
    protected $pdfDemand;

    /**
     * @var TsTCPDF
     */
    protected $pdf;

    /**
     * @var string
     */
    protected $pdf_path = 'typo3temp/cart_pdf/';

    /**
     * @var string
     */
    protected $pdfFilename = 'test';

    /**
     * @var int
     */
    protected $border = 1;

    public function __construct(
        ConfigurationManager $configurationManager,
        OrderItemRepository $orderItemRepository,
        PersistenceManager $persistenceManager,
        ResourceFactory $resourceFactory,
        StorageRepository $storageRepository
    ) {
        $this->configurationManager = $configurationManager;
        $this->orderItemRepository = $orderItemRepository;
        $this->resourceFactory = $resourceFactory;
        $this->persistenceManager = $persistenceManager;
        $this->storageRepository = $storageRepository;
    }

    /**
     * @param OrderItem $orderItem
     * @param string $pdfType
     */
    public function createPdf(OrderItem $orderItem, string $pdfType): void
    {
        $this->setPluginSettings($pdfType);

        $pdfFilename = '/tmp/tempfile.pdf';

        $this->renderPdf($orderItem, $pdfType);

        $getNumber = 'get' . ucfirst($pdfType) . 'Number';
        $newFileName = $orderItem->$getNumber() . '.pdf';

        if (file_exists($pdfFilename)) {
            $storage = $this->storageRepository->findByUid($this->pdfSettings['storageRepository']);
            $targetFolder = $storage->getFolder($this->pdfSettings['storageFolder']);

            if (class_exists('\TYPO3\CMS\Core\Resource\DuplicationBehavior')) {
                $conflictMode = \TYPO3\CMS\Core\Resource\DuplicationBehavior::RENAME;
            } else {
                $conflictMode = 'changeName';
            }

            $falFile = $targetFolder->addFile(
                $pdfFilename,
                $newFileName,
                $conflictMode
            );

            $falFileReference = $this->createFileReferenceFromFalFileObject($falFile);

            $addPdfFunction = 'add' . ucfirst($pdfType) . 'Pdf';
            $orderItem->$addPdfFunction($falFileReference);
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

        $this->pdf = GeneralUtility::makeInstance(
            TsTCPDF::class
        );
        $this->pdf->setSettings($pluginSettings);
        $this->pdf->setCartPdfType($pdfType . 'Pdf');

        if (!$this->pdfSettings['header']) {
            $this->pdf->setPrintHeader(false);
        } else {
            if ($this->pdfSettings['header']['margin']) {
                $this->pdf->setHeaderMargin($this->pdfSettings['header']['margin']);
                $this->pdf->SetMargins(PDF_MARGIN_LEFT, $this->pdfSettings['header']['margin'], PDF_MARGIN_RIGHT);
            } else {
                $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            }
        }
        if (!$this->pdfSettings['footer']) {
            $this->pdf->setPrintFooter(false);
        } else {
            if ($this->pdfSettings['footer']['margin']) {
                $this->pdf->setFooterMargin($this->pdfSettings['footer']['margin']);
                $this->pdf->setAutoPageBreak(true, $this->pdfSettings['footer']['margin']);
            } else {
                $this->pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            }
        }

        $this->pdf->AddPage();

        $font = 'Helvetica';
        if ($this->pdfSettings['font']) {
            $font = $this->pdfSettings['font'];
        }

        $fontStyle = '';
        if ($this->pdfSettings['fontStyle']) {
            $fontStyle = $this->pdfSettings['fontStyle'];
        }

        $fontSize = 8;
        if ($this->pdfSettings['fontSize']) {
            $fontSize = $this->pdfSettings['fontSize'];
        }

        $this->pdf->SetFont($font, $fontStyle, $fontSize);

        $colorArray = [0, 0, 0];
        if ($this->pdfSettings['drawColor']) {
            $colorArray = explode(',', $this->pdfSettings['drawColor']);
        }
        $this->pdf->setDrawColorArray($colorArray);

        $this->renderMarker();

        if ($this->pdfSettings['letterhead']['html']) {
            foreach ($this->pdfSettings['letterhead']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . 'Pdf/Letterhead/';
                $assignToView = ['orderItem' => $orderItem];
                $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        if ($this->pdfSettings['body']['before']['html']) {
            foreach ($this->pdfSettings['body']['before']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . 'Pdf/Body/Before/';
                $assignToView = ['orderItem' => $orderItem];
                $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        $this->renderCart($orderItem, $pdfType);

        if ($this->pdfSettings['body']['after']['html']) {
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

        $config = $this->pdfSettings['body']['order'];
        $config['height'] = 0;

        if (!$config['spacingY'] && !$config['positionY']) {
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
        $headerOut = trim(preg_replace('~[\n]+~', '', $header));

        return $headerOut;
    }

    protected function renderCartBody(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->pdf->getStandaloneView('/' . ucfirst($pdfType) . '/Order/', 'Product');
        $view->assign('orderItem', $orderItem);

        $bodyOut = '';

        foreach ($orderItem->getProducts() as $product) {
            $config['$positionY'] = $this->pdf->GetY();
            $view->assign('product', $product);
            $product = $view->render();

            $bodyOut .= trim(preg_replace('~[\n]+~', '', $product));
        }

        return $bodyOut;
    }

    protected function renderCartFooter(OrderItem $orderItem, string $pdfType): string
    {
        $view = $this->pdf->getStandaloneView('/' . ucfirst($pdfType) . '/Order/', 'Footer');
        $view->assign('orderSettings', $this->pdfSettings['body']['order']);
        $view->assign('orderItem', $orderItem);
        $footer = $view->render();
        $footerOut = trim(preg_replace('~[\n]+~', '', $footer));

        return $footerOut;
    }

    /**
     * @param string $pdfType
     */
    protected function setPluginSettings(string $pdfType)
    {
        if (TYPO3_MODE === 'BE') {
            $pageId = (int)(GeneralUtility::_GET('id')) ? GeneralUtility::_GET('id') : 1;

            $frameworkConfiguration = $this->configurationManager->getConfiguration(
                ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK
            );
            $persistenceConfiguration = ['persistence' => ['storagePid' => $pageId]];
            $this->configurationManager->setConfiguration(
                array_merge($frameworkConfiguration, $persistenceConfiguration)
            );
        }

        $this->pluginSettings =
            $this->configurationManager->getConfiguration(
                ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK,
                'CartPdf'
            );

        $this->cartSettings =
            $this->configurationManager->getConfiguration(
                ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK,
                'Cart'
            );

        $this->pdfSettings = $this->pluginSettings[$pdfType . 'Pdf'];

        $this->pdfDemand = GeneralUtility::makeInstance(PdfDemand::class);

        $this->pdfDemand->setFontSize(
            (int)$this->pdfSettings['fontSize']
        );

        $this->pdfDemand->setDebug(
            (int)$this->pdfSettings['debug']
        );

        $this->pdfDemand->setFoldMarksEnabled(
            (bool)$this->pdfSettings['enableFoldMarks']
        );

        $this->pdfDemand->setAddressFieldMarksEnabled(
            (bool)$this->pdfSettings['enableAddressFieldMarks']
        );
    }

    protected function createFileReferenceFromFalFileObject(File $file): FileReference
    {
        $falFileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => uniqid('NEW_'),
                'uid' => uniqid('NEW_'),
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
