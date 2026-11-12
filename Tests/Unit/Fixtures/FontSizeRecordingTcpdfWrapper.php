<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Tests\Unit\Fixtures;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\CartPdf\Service\TcpdfWrapper;

/**
 * Records the font size used for writing instead of rendering HTML.
 */
final class FontSizeRecordingTcpdfWrapper extends TcpdfWrapper
{
    /**
     * @var float[]
     */
    public array $fontSizesWhileWriting = [];

    public function writeHTMLCell($w, $h, $x, $y, $html = '', $border = 0, $ln = 0, $fill = false, $reseth = true, $align = '', $autopadding = true)
    {
        $this->fontSizesWhileWriting[] = (float)$this->getFontSizePt();
    }
}
