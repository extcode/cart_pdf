.. include:: ../../Includes.rst.txt

=============================================================
Breaking: #35 - Extract writing document to FileWriterService
=============================================================

See `Issue 135 <https://github.com/extcode/cart_products/issues/135>`__

Description
===========

To enable the PDF content to be written using a separate file name, writing
the rendered content should be moved to a separate service.

Your own service can then be integrated via dependency injection.

Affected Installations
======================

All installations which overwrite
`\Extcode\CartPdf\Service\PdfService::createPdf` and 
`\Extcode\CartPdf\Service\PdfService::renderPdf`
are affected.
These methods were removed. The functionality was
moved to `\Extcode\CartPdf\Service\PdfService::renderDocument`
and return the pdf content as string. Saving the file was
handed over to the new
`\Extcode\CartPdf\Service\FileWriterService::writeContentToFile`.

Migration
=========

Move the logic to an own implementation of the
`\Extcode\CartPdf\Service\FileWriterServiceInterface` and
inject this own service to the
`\Extcode\CartPdf\EventListener\Order\Finish\DocumentRenderer`.

.. index:: API
