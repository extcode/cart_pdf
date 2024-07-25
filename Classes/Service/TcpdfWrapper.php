<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class TcpdfWrapper extends \TCPDF
{
    /**
     * @var array
     */
    protected array $pdfSettings = [];

    /**
     * @var string
     */
    protected string $pdfType = '';

    /**
     * @return string
     */
    public function getCartPdfType(): string
    {
        return $this->pdfType;
    }

    /**
     * @param string $pdfType
     */
    public function setCartPdfType(string $pdfType): void
    {
        $this->pdfType = $pdfType;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): void
    {
        $this->pdfSettings = $settings;
    }

    public function header(): void
    {
        if (!empty($this->pdfSettings[$this->pdfType])) {
            if (!empty($this->pdfSettings[$this->pdfType]['header'])) {
                if (!empty($this->pdfSettings[$this->pdfType]['fontSize'])) {
                    $this->SetFontSize($this->pdfSettings[$this->pdfType]['fontSize']);
                }

                if (!empty($this->pdfSettings[$this->pdfType]['header']['html'])) {
                    foreach ($this->pdfSettings[$this->pdfType]['header']['html'] as $partName => $partConfig) {
                        $this->renderStandaloneView(
                            $this->pdfSettings[$this->pdfType]['header']['html'][$partName]['templatePath'],
                            $partName,
                            $partConfig
                        );
                    }
                }

                if (!empty($this->pdfSettings[$this->pdfType]['header']['line'])) {
                    foreach ($this->pdfSettings[$this->pdfType]['header']['line'] as $partName => $partConfig) {
                        $this->Line(
                            $partConfig['x1'],
                            $partConfig['y1'],
                            $partConfig['x2'],
                            $partConfig['y2'],
                            $partConfig['style']
                        );
                    }
                }
            }
        }
    }

    public function footer(): void
    {
        if (!empty($this->pdfSettings[$this->pdfType])) {
            if (!empty($this->pdfSettings[$this->pdfType]['footer'])) {
                if (!empty($this->pdfSettings[$this->pdfType]['fontSize'])) {
                    $this->SetFontSize($this->pdfSettings[$this->pdfType]['fontSize']);
                }

                if (!empty($this->pdfSettings[$this->pdfType]['footer']['html'])) {
                    foreach ($this->pdfSettings[$this->pdfType]['footer']['html'] as $partName => $partConfig) {
                        $this->renderStandaloneView(
                            $this->pdfSettings[$this->pdfType]['footer']['html'][$partName]['templatePath'],
                            $partName,
                            $partConfig
                        );
                    }
                }

                if (!empty($this->pdfSettings[$this->pdfType]['footer']['line'])) {
                    foreach ($this->pdfSettings[$this->pdfType]['footer']['line'] as $partName => $partConfig) {
                        $this->Line(
                            $partConfig['x1'],
                            $partConfig['y1'],
                            $partConfig['x2'],
                            $partConfig['y2'],
                            $partConfig['style']
                        );
                    }
                }
            }
        }
    }

    /**
     * render Standalone View
     *
     * @param string $templatePath
     * @param string $type
     * @param array $config
     * @param array $assignToView
     */
    public function renderStandaloneView(
        string $templatePath,
        string $type,
        array $config,
        array $assignToView = []
    ): void {
        $view = $this->getStandaloneView($templatePath, ucfirst($type));

        if (!empty($config['file'])) {
            $file = GeneralUtility::getFileAbsFileName($config['file']);
            $view->assign('file', $file);

            if (!empty($config['width'])) {
                $view->assign('width', $config['width']);
            }

            if (!empty($config['height'])) {
                $view->assign('heigth', $config['heigth']);
            }
        }

        if ($type === 'page') {
            $view->assign('numPage', $this->getAliasNumPage());
            $view->assign('numPages', $this->getAliasNbPages());
        }

        $view->assignMultiple($assignToView);

        $content = (string)$view->render();
        $content = trim(preg_replace('~[\\n]+~', '', $content));

        $this->writeHtmlCellWithConfig($content, $config);
    }

    /**
     * Write HTML Cell with configuration
     *
     * @param string $content
     * @param array $config
     */
    public function writeHtmlCellWithConfig(string $content, array $config): void
    {
        $width = $config['width'];
        $height = 0;

        if (!empty($config['height'])) {
            $height = $config['height'];
        }
        $positionX = $config['positionX'];
        $positionY = $config['positionY'];
        $align = 'L';

        if (!empty($config['align'])) {
            $align = $config['align'];
        }

        $oldFontSize = $this->getFontSizePt();

        if (!empty($config['fontSize'])) {
            $this->SetFontSize($config['fontSize']);
        }

        if (!empty($config['spacingY'])) {
            $this->setY($this->getY() + $config['spacingY']);
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
     * Get standalone View
     *
     * @param string $templatePath
     * @param string $templateFileName
     * @param string $format
     *
     * @return StandaloneView
     */
    public function getStandaloneView(string $templatePath, string $templateFileName = 'Default', string $format = 'html'): StandaloneView
    {
        $templatePathAndFileName = $templatePath . $templateFileName . '.' . $format;

        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setFormat($format);

        if (!empty($this->pdfSettings['view'])) {
            $view->setLayoutRootPaths($this->pdfSettings['view']['layoutRootPaths']);
            $view->setPartialRootPaths($this->pdfSettings['view']['partialRootPaths']);

            if ($this->pdfSettings['view']['templateRootPaths']) {
                foreach ($this->pdfSettings['view']['templateRootPaths'] as $pathNameKey => $pathNameValue) {
                    $templateRootPath = GeneralUtility::getFileAbsFileName(
                        $pathNameValue
                    );

                    $completePath = $templateRootPath . $templatePathAndFileName;

                    if (file_exists($completePath)) {
                        $view->setTemplatePathAndFilename($completePath);
                    }
                }
            }
        }

        return $view;
    }
}
