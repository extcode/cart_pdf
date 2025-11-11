<?php

declare(strict_types=1);

/*
 * (c) 2025 rc design visual concepts (rc-design.at)
 * _________________________________________________
 * The TYPO3 project - inspiring people to share!
 * _________________________________________________
 */

namespace Extcode\CartPdf\Service;

use RuntimeException;
use TCPDF;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;

/**
 * Wrapper-Klasse für TCPDF zur Nutzung innerhalb der TYPO3 Cart PDF-Extension.
 */
class TcpdfWrapper extends TCPDF
{
    /**
     * @var array Einstellungen für das PDF
     */
    protected array $pdfSettings = [];

    /**
     * @var string Typ des aktuellen PDF (z. B. 'order')
     */
    protected string $pdfType = '';

    /**
     * @var ViewFactoryInterface
     */
    protected ViewFactoryInterface $viewFactory;

    public function __construct(
        $orientation = 'P',
        $unit = 'mm',
        $format = 'A4',
        $unicode = true,
        $encoding = 'UTF-8',
        $diskcache = false,
        $pdfa = false
    ) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
    }

    /**
     * Gibt den PDF-Typ zurück.
     */
    public function getCartPdfType(): string
    {
        return $this->pdfType;
    }

    /**
     * Setzt den PDF-Typ.
     */
    public function setCartPdfType(string $pdfType): void
    {
        $this->pdfType = $pdfType;
    }

    /**
     * Setzt die PDF-Einstellungen.
     */
    public function setSettings(array $settings): void
    {
        $this->pdfSettings = $settings;
    }

    /**
     * Kopfbereich des PDF rendern.
     */
    public function header(): void
    {
        if (!empty($this->pdfSettings[$this->pdfType]['header'])) {
            if (!empty($this->pdfSettings[$this->pdfType]['fontSize'])) {
                $this->SetFontSize($this->pdfSettings[$this->pdfType]['fontSize']);
            }

            if (!empty($this->pdfSettings[$this->pdfType]['header']['html'])) {
                foreach ($this->pdfSettings[$this->pdfType]['header']['html'] as $partName => $partConfig) {
                    $this->renderStandaloneView(
                        $partConfig['templatePath'] ?? '',
                        $partName,
                        $partConfig
                    );
                }
            }

            if (!empty($this->pdfSettings[$this->pdfType]['header']['line'])) {
                foreach ($this->pdfSettings[$this->pdfType]['header']['line'] as $partConfig) {
                    $this->Line(
                        $partConfig['x1'] ?? 0,
                        $partConfig['y1'] ?? 0,
                        $partConfig['x2'] ?? 0,
                        $partConfig['y2'] ?? 0,
                        $partConfig['style'] ?? []
                    );
                }
            }
        }
    }

    /**
     * Fußbereich des PDF rendern.
     */
    public function footer(): void
    {
        if (!empty($this->pdfSettings[$this->pdfType]['footer'])) {
            if (!empty($this->pdfSettings[$this->pdfType]['fontSize'])) {
                $this->SetFontSize($this->pdfSettings[$this->pdfType]['fontSize']);
            }

            if (!empty($this->pdfSettings[$this->pdfType]['footer']['html'])) {
                foreach ($this->pdfSettings[$this->pdfType]['footer']['html'] as $partName => $partConfig) {
                    $this->renderStandaloneView(
                        $partConfig['templatePath'] ?? '',
                        $partName,
                        $partConfig
                    );
                }
            }

            if (!empty($this->pdfSettings[$this->pdfType]['footer']['line'])) {
                foreach ($this->pdfSettings[$this->pdfType]['footer']['line'] as $partConfig) {
                    $this->Line(
                        $partConfig['x1'] ?? 0,
                        $partConfig['y1'] ?? 0,
                        $partConfig['x2'] ?? 0,
                        $partConfig['y2'] ?? 0,
                        $partConfig['style'] ?? []
                    );
                }
            }
        }
    }

    /**
     * Rendert einen Fluid-View und schreibt ihn als HTML-Block ins PDF.
     *
     * @param string $templatePath Pfad zum Template-Verzeichnis
     * @param string $type         Name des Bereichs (z. B. 'header', 'footer')
     * @param array  $config       Konfiguration des Elements
     * @param array  $assignToView Zusätzliche Fluid-Variablen
     */
    public function renderStandaloneView(
        string $templatePath,
        string $type,
        array $config,
        array $assignToView = []
    ): void {
        $view = $this->getFluidView($templatePath, ucfirst($type));

        if (!empty($config['file'])) {
            $file = $config['file'];

            // Prüfen ob URL oder lokaler Pfad
            if (!preg_match('~^https?://~', $file)) {
                $resolvedFile = GeneralUtility::getFileAbsFileName($file);
                if ($resolvedFile && file_exists($resolvedFile)) {
                    $file = $resolvedFile;

                    // Base64 + MIME-Typ nur bei lokalen Dateien ermitteln
                    $view->assign('file_base64', base64_encode((string)file_get_contents($resolvedFile)));
                    $view->assign('file_mimetype', mime_content_type($resolvedFile));
                }
            }

            $view->assign('file', $file);

            if (!empty($config['width'])) {
                $view->assign('width', $config['width']);
            }
            if (!empty($config['height'])) {
                $view->assign('height', $config['height']);
            }
        }

        if ($type === 'page') {
            $view->assign('numPage', $this->getAliasNumPage());
            $view->assign('numPages', $this->getAliasNbPages());
        }

        $view->assignMultiple($assignToView);

        $content = trim(preg_replace('~[\n]+~', '', (string)$view->render()));

        $this->writeHtmlCellWithConfig($content, $config);
    }

    /**
     * Schreibt einen HTML-Block an die im Config angegebenen Position.
     *
     * @param string $content HTML-Inhalt
     * @param array  $config  Konfiguration für Position, Größe und Ausrichtung
     */
    public function writeHtmlCellWithConfig(string $content, array $config): void
    {
        $width = $config['width'];
        $height = 0;
        if (isset($config['height'])) {
            $height = $config['height'];
        }
        $positionX = $config['positionX'] ?? '';
        $positionY = $config['positionY'] ?? '';
        $align = 'L';

        $oldFontSize = $this->getFontSizePt();

        if (!empty($config['fontSize'])) {
            $this->SetFontSize($config['fontSize']);
        }

        if (!empty($config['spacingY'])) {
            $this->setY($this->getY() + (float)$config['spacingY']);
        }

        $this->writeHTMLCell(
            $width,
            $height,
            $positionX,
            $positionY,
            $content,
            false,
            2,
            false,
            true,
            $align,
            true
        );

        if (!empty($config['fontSize'])) {
            $this->SetFontSize($oldFontSize);
        }
    }

    /**
     * Erstellt einen Fluid-View für die PDF-Templates (TYPO3 v13 kompatibel).
     *
     * @param string $templatePath
     * @param string $templateFileName
     * @param string $format
     */
    public function getFluidView(
        string $templatePath,
        string $templateFileName = 'Default',
        string $format = 'html'
    ): FluidViewAdapter {
        $templatePathAndFileName = $templatePath . $templateFileName . '.' . $format;

        // TYPO3 v13: ViewFactoryData erstellen
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        // ViewFactoryData mit korrekten Parametern erstellen
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: $this->pdfSettings['view']['templateRootPaths'] ?? [],
            partialRootPaths: $this->pdfSettings['view']['partialRootPaths'] ?? [],
            layoutRootPaths: $this->pdfSettings['view']['layoutRootPaths'] ?? [],
            request: $request,
        );

        $view = $this->viewFactory->create($viewFactoryData);

        // Sicherstellen, dass wir einen FluidViewAdapter haben
        if (!$view instanceof FluidViewAdapter) {
            throw new RuntimeException('Created view must be an instance of FluidViewAdapter');
        }

        $renderingContext = $view->getRenderingContext();
        $renderingContext->setControllerAction('');

        // Template-Pfad setzen
        if (!empty($this->pdfSettings['view']['templateRootPaths'])) {
            $paths = $renderingContext->getTemplatePaths();

            foreach ($this->pdfSettings['view']['templateRootPaths'] as $path) {
                $templateRootPath = GeneralUtility::getFileAbsFileName($path);
                $completePath = $templateRootPath . $templatePathAndFileName;

                if (file_exists($completePath)) {
                    $paths->setTemplatePathAndFilename($completePath);
                    break;
                }
            }
        }

        return $view;
    }
}
