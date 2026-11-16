.. include:: ../../Includes.rst.txt

=====================================================
Breaking: #46 - Move view rendering to the PdfService
=====================================================

See `Pull Request #46 <https://github.com/extcode/cart_pdf/pull/46>`__

Description
===========

``\Extcode\CartPdf\Service\TcpdfWrapper`` no longer renders Fluid views. It
only arranges the content of the PDF. Creating and rendering the views moved
to ``\Extcode\CartPdf\Service\PdfService``, which hands the rendered content
over to the ``TcpdfWrapper``. This separates the rendering of the content from
the PDF library.

This changes the public API introduced in 8.0:

*  ``TcpdfWrapper::createView()`` and ``TcpdfWrapper::renderView()`` are
   removed. The views are created and rendered by private methods of the
   ``PdfService`` now. Templates are resolved via the TypoScript setting
   ``view.templateRootPaths``.
*  The constructor and ``TcpdfWrapper::createWithTypeAndSettings()`` no
   longer expect a ``\TYPO3\CMS\Core\View\ViewFactoryInterface`` as first
   argument.
*  The HTML parts of header and footer are no longer rendered by
   ``TcpdfWrapper::Header()`` and ``TcpdfWrapper::Footer()``. The
   ``PdfService`` renders them before the first page is added and passes them
   via the new methods ``TcpdfWrapper::addHeaderPart()`` and
   ``TcpdfWrapper::addFooterPart()``.

The configuration via TypoScript and the templates are unchanged. The
rendered documents do not change.

Affected Installations
======================

Installations which create a ``TcpdfWrapper`` or call
``TcpdfWrapper::createView()`` or ``TcpdfWrapper::renderView()`` in their own
code, e.g. in an own implementation of
``\Extcode\CartPdf\Service\DocumentRenderServiceInterface`` or in a class
extending ``TcpdfWrapper``.

Installations which configure the documents via TypoScript and templates
only are not affected.

Migration
=========

*  Remove the ``\TYPO3\CMS\Core\View\ViewFactoryInterface`` argument from
   ``new TcpdfWrapper()`` and ``TcpdfWrapper::createWithTypeAndSettings()``.
*  Create and render own views with
   ``\TYPO3\CMS\Core\View\ViewFactoryInterface`` and write the rendered
   content with ``TcpdfWrapper::writeHtmlCellWithConfig()``.
*  Render own header and footer parts in advance and pass them via
   ``TcpdfWrapper::addHeaderPart()`` and ``TcpdfWrapper::addFooterPart()``.

.. index:: API
