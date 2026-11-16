.. include:: ../../Includes.rst.txt

===========================================================
Breaking: Rename template Order/Footer.html to Summary.html
===========================================================

Description
===========

The product table of a document can end with the subtotal on each page and
with the summary of the order on the last page. To tell both apart, the
template of the summary is renamed from ``Order/Footer.html`` to
``Order/Summary.html``, e.g. ``InvoicePdf/Order/Summary.html``. The subtotal
is rendered with the new template ``Order/Subtotal.html``.

The variables of the template are unchanged.

Affected Installations
======================

Installations with an own ``Order/Footer.html`` for the order, the invoice or
the delivery note in their template root path. The template is no longer
used, the summary of the extension is rendered instead.

Migration
=========

Rename the own templates, e.g.:

*  ``InvoicePdf/Order/Footer.html`` to ``InvoicePdf/Order/Summary.html``
*  ``OrderPdf/Order/Footer.html`` to ``OrderPdf/Order/Summary.html``
*  ``DeliveryPdf/Order/Footer.html`` to ``DeliveryPdf/Order/Summary.html``

.. index:: Frontend, Backend
