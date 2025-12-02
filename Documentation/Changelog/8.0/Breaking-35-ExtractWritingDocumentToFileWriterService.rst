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
`\Extcode\CartPdf\Service\PdfService::createPdf` are affected.
This Method was removed. THe `\Extcode\CartPdf\Service\PdfService::renderPdf`
is now public and return the pdf content as string which is
handed over to the new `\Extcode\CartPdf\Service\FileWriterService::writeContentToFile`.

Migration
=========

Move the own logic in the `\Extcode\CartPdf\Service\PdfService::createPdf`
to an own `FileWriterService` and inject this own service to the
`\Extcode\CartPdf\Service\PdfService`.FileWriterService

.. index:: API