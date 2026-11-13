.. include:: ../../Includes.rst.txt

=================================================
Breaking: Replace StandaloneView with ViewFactory
=================================================

Description
===========

The Fluid ``StandaloneView`` is deprecated since TYPO3 v13 and will be
removed in TYPO3 v14. ``\Extcode\CartPdf\Service\TcpdfWrapper`` creates its
views via ``\TYPO3\CMS\Core\View\ViewFactoryInterface`` now. The view factory
is injected into ``\Extcode\CartPdf\Service\PdfService`` and handed over to
the ``TcpdfWrapper``.

This changes the public API of ``TcpdfWrapper``:

*  ``TcpdfWrapper::getStandaloneView()`` is renamed to
   ``TcpdfWrapper::createView()``. It returns a
   ``\TYPO3\CMS\Core\View\ViewInterface`` instead of a
   ``\TYPO3\CMS\Fluid\View\StandaloneView``.
*  ``TcpdfWrapper::renderStandaloneView()`` is renamed to
   ``TcpdfWrapper::renderView()``. The arguments are unchanged.
*  The constructor and ``TcpdfWrapper::createWithTypeAndSettings()`` expect
   a ``\TYPO3\CMS\Core\View\ViewFactoryInterface`` as first argument.

The template resolution via the TypoScript settings ``view.templateRootPaths``,
``view.partialRootPaths`` and ``view.layoutRootPaths`` is unchanged. The
rendered documents do not change.

Affected Installations
======================

Installations which create a ``TcpdfWrapper`` or call
``TcpdfWrapper::getStandaloneView()`` or
``TcpdfWrapper::renderStandaloneView()`` in their own code, e.g. in an own
implementation of ``\Extcode\CartPdf\Service\DocumentRenderServiceInterface``
or in a class extending ``TcpdfWrapper``.

Installations which configure the documents via TypoScript and templates
only are not affected.

Migration
=========

*  Inject ``\TYPO3\CMS\Core\View\ViewFactoryInterface`` and pass it as first
   argument to ``new TcpdfWrapper()`` or
   ``TcpdfWrapper::createWithTypeAndSettings()``.
*  Replace calls of ``getStandaloneView()`` with ``createView()``. Use only
   the methods of ``\TYPO3\CMS\Core\View\ViewInterface`` (``assign()``,
   ``assignMultiple()``, ``render()``) on the returned view and adapt type
   hints accordingly.
*  Replace calls of ``renderStandaloneView()`` with ``renderView()``.

.. index:: API
