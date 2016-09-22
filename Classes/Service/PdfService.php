<?php

namespace Extcode\CartPdf\Service;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Pdf Service
 *
 * @package cart_pdf
 * @author Daniel Lorenz <ext.cart.pdf@extco.de>
 */
class PdfService
{
    /**
     * Object Manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Configuration Manager
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * Persistence Manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    /**
     * Plugin Settings
     *
     * @var array
     */
    protected $pluginSettings;

    /**
     * Cart Settings
     *
     * @var array
     */
    protected $cartSettings;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     * @inject
     */
    protected $resourceFactory;

    /**
     * Order Item Repository
     *
     * @var \Extcode\Cart\Domain\Repository\Order\ItemRepository
     */
    protected $itemRepository;

    /**
     * Data Transfer Object PdfDemand
     *
     * @var \Extcode\CartPdf\Domain\Model\Dto\PdfDemand
     */
    protected $pdfDemand;

    /**
     * @var \Extcode\Tcpdf\Service\TSPdf
     */
    protected $pdf;

    /**
     * PDF Path
     *
     * @var string
     */
    protected $pdf_path = 'typo3temp/cart_pdf/';

    /**
     * PDF Filename
     *
     * @var string
     */
    protected $pdf_filename = 'test';

    /**
     * Enable/Disable Border for debugging
     *
     * @var int
     */
    protected $border = 1;

    /**
     * Injects the Object Manager
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     *
     * @return void
     */
    public function injectObjectManager(
        \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Injects the Configuration Manager
     *
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     *
     * @return void
     */
    public function injectConfigurationManager(
        \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager
     */
    public function injectPersistenceManager(
        \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager
    ) {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @param \Extcode\Cart\Domain\Repository\Order\ItemRepository $itemRepository
     */
    public function injectItemRepository(
        \Extcode\Cart\Domain\Repository\Order\ItemRepository $itemRepository
    ) {
        $this->itemRepository = $itemRepository;
    }

    /**
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     *
     * @return void
     */
    public function createPdf($pdfType, \Extcode\Cart\Domain\Model\Order\Item $orderItem)
    {
        $this->setPluginSettings($pdfType);

        $pdfFilename = '/tmp/tempfile.pdf';

        $this->renderPdf($pdfType, $orderItem);

        $storageRepository = $this->objectManager->get(
            \TYPO3\CMS\Core\Resource\StorageRepository::class
        );

        $newFileName = $orderItem->getInvoiceNumber() . '.pdf';

        if (file_exists($pdfFilename)) {
            /** @var \TYPO3\CMS\Core\Resource\ResourceStorage $storage */
            $storage = $storageRepository->findByUid('1');
            $targetFolder = $storage->getFolder('uploads/tx_cart/incoice_pdf');

            $falFile = $targetFolder->addFile(
                $pdfFilename,
                $newFileName,
                \TYPO3\CMS\Core\Resource\DuplicationBehavior::RENAME
            );

            $falFileReference = $this->createFileReferenceFromFalFileObject($falFile);

            $orderItem->addInvoicePdf($falFileReference);
        }

        $this->itemRepository->update($orderItem);
        // Not neccessary since 6.2
        $this->persistenceManager->persistAll();
    }

    /**
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     *
     * @return void
     */
    protected function renderPdf($pdfType, $orderItem)
    {
        $pluginSettings = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'cartpdf'
        );

        $this->pdf = $this->objectManager->get(
            \Extcode\TCPDF\Service\TsTCPDF::class
        );
        $this->pdf->setSettings($pluginSettings);
        $this->pdf->setCartPdfType($pdfType);

        if (!$this->pluginSettings[$pdfType]['header']) {
            $this->pdf->setPrintHeader(false);
        } else {
            if ($this->pluginSettings[$pdfType]['header']['margin']) {
                $this->pdf->setHeaderMargin($this->pluginSettings[$pdfType]['header']['margin']);
                $this->pdf->SetMargins(PDF_MARGIN_LEFT, $this->pluginSettings[$pdfType]['header']['margin'], PDF_MARGIN_RIGHT);
            } else {
                $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            }
        }
        if (!$this->pluginSettings[$pdfType]['footer']) {
            $this->pdf->setPrintFooter(false);
        } else {
            if ($this->pluginSettings[$pdfType]['footer']['margin']) {
                $this->pdf->setFooterMargin($this->pluginSettings[$pdfType]['footer']['margin']);
                $this->pdf->setAutoPageBreak(true, $this->pluginSettings[$pdfType]['footer']['margin']);
            } else {
                $this->pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            }
        }

        $this->pdf->AddPage();

        $font = 'Helvetica';
        if ($this->pluginSettings[$pdfType]['font']) {
            $font = $this->pluginSettings[$pdfType]['font'] ;
        }

        $fontStyle = '';
        if ($this->pluginSettings[$pdfType]['fontStyle']) {
            $fontStyle = $this->pluginSettings[$pdfType]['fontStyle'] ;
        }

        $fontSize = 8;
        if ($this->pluginSettings[$pdfType]['fontSize']) {
            $fontSize = $this->pluginSettings[$pdfType]['fontSize'] ;
        }

        $this->pdf->SetFont($font, $fontStyle, $fontSize);

        $colorArray = [0,0,0];
        if ($this->pluginSettings[$pdfType]['drawColor']) {
            $colorArray = explode(',', $this->pluginSettings[$pdfType]['drawColor']);
        }
        $this->pdf->setDrawColorArray($colorArray);

        $this->renderMarker();

        if ($this->pluginSettings[$pdfType]['letterhead']['html']) {
            foreach ($this->pluginSettings[$pdfType]['letterhead']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Letterhead/';
                $assignToView = ['orderItem' => $orderItem];
                $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        if ($this->pluginSettings[$pdfType]['body']['before']['html']) {
            foreach ($this->pluginSettings[$pdfType]['body']['before']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Body/Before/';
                $assignToView = ['orderItem' => $orderItem];
                $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        $this->renderCart($pdfType, $orderItem);

        if ($this->pluginSettings[$pdfType]['body']['after']['html']) {
            foreach ($this->pluginSettings[$pdfType]['body']['after']['html'] as $partName => $partConfig) {
                $templatePath = '/' . ucfirst($pdfType) . '/Body/After/';
                $assignToView = ['orderItem' => $orderItem];
                $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
            }
        }

        $pdfFilename = '/tmp/tempfile.pdf';

        $this->pdf->Output($pdfFilename, 'F');
    }

    /**
     *
     */
    protected function renderMarker()
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

    /**
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     */
    protected function renderCart($pdfType, $orderItem)
    {
        $config = $this->pluginSettings[$pdfType]['body']['order'];
        $config['height'] = 0;

        if (!$config['spacingY'] && !$config['positionY']) {
            $config['spacingY'] = 5;
        }

        $headerOut = $this->renderCartHeader($pdfType, $orderItem);
        $bodyOut = $this->renderCartBody($pdfType, $orderItem);
        $footerOut = $this->renderCartFooter($pdfType, $orderItem);

        $content = '<table cellpadding="3">' . $headerOut . $bodyOut . $footerOut . '</table>';

        $this->pdf->writeHtmlCellWithConfig($content, $config);
    }

    /**
     * Render Cart Header
     *
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     *
     * @return string
     */
    protected function renderCartHeader($pdfType, $orderItem)
    {
        $view = $this->pdf->getStandaloneView('/' . ucfirst($pdfType) . '/Order/', 'Header');
        $view->assign('orderItem', $orderItem);
        $header = $view->render();
        $headerOut = trim(preg_replace('~[\n]+~', '', $header));

        return $headerOut;
    }

    /**
     * Render Cart Body
     *
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     *
     * @return string
     */
    protected function renderCartBody($pdfType, $orderItem)
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

    /**
     * Render Cart Footer
     *
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     *
     * @return string
     */
    protected function renderCartFooter($pdfType, $orderItem)
    {
        $view = $this->pdf->getStandaloneView('/' . ucfirst($pdfType) . '/Order/', 'Footer');
        $view->assign('orderSettings', $this->pluginSettings[$pdfType]['body']['order']);
        $view->assign('orderItem', $orderItem);
        $footer = $view->render();
        $footerOut = trim(preg_replace('~[\n]+~', '', $footer));

        return $footerOut;
    }

    /**
     * Sets Plugin Settings
     *
     * @param string $pdfType
     *
     * @return void
     */
    protected function setPluginSettings($pdfType)
    {
        $this->pluginSettings =
            $this->configurationManager->getConfiguration(
                \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'cartpdf'
            );

        $this->cartSettings =
            $this->configurationManager->getConfiguration(
                \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'cart'
            );

        $this->pdfDemand = $this->objectManager->get(
            \Extcode\CartPdf\Domain\Model\Dto\PdfDemand::class
        );

        $this->pdfDemand->setFontSize(
            $this->pluginSettings[$pdfType]['fontSize']
        );

        $this->pdfDemand->setDebug(
            $this->pluginSettings[$pdfType]['debug']
        );

        $this->pdfDemand->setFoldMarksEnabled(
            boolval($this->pluginSettings[$pdfType]['enableFoldMarks'])
        );

        $this->pdfDemand->setAddressFieldMarksEnabled(
            boolval($this->pluginSettings[$pdfType]['enableAddressFieldMarks'])
        );
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $file
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    protected function createFileReferenceFromFalFileObject(\TYPO3\CMS\Core\Resource\File $file)
    {
        $falFileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => uniqid('NEW_'),
                'uid' => uniqid('NEW_'),
                'crop' => null,
            ]
        );

        $fileReference = $this->objectManager->get(
            \TYPO3\CMS\Extbase\Domain\Model\FileReference::class
        );

        $fileReference->setOriginalResource($falFileReference);

        return $fileReference;
    }
}
