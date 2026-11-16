.. include:: ../../Includes.rst.txt

===========================================================
Feature: #66 - Generate documents via GenerateDocumentEvent
===========================================================

See `Pull Request #66 <https://github.com/extcode/cart_pdf/pull/66>`__

Description
===========

cart v12 no longer calls the services of cart_pdf when a document is
created in the order backend module. It dispatches the
``\Extcode\Cart\Event\Document\GenerateDocumentEvent`` instead.

The new event listener
``\Extcode\CartPdf\EventListener\Document\GenerateDocument`` renders the
requested document with
``\Extcode\CartPdf\Service\DocumentRenderServiceInterface``, writes it with
``\Extcode\CartPdf\Service\FileWriterServiceInterface`` and attaches it to
the order item.

Own implementations of these interfaces are used for documents created in
the order backend module as well.

.. index:: API
