.. include:: ../../Includes.rst.txt

============================
Feature: #46 - Add site sets
============================

See `Pull Request #46 <https://github.com/extcode/cart_pdf/pull/46>`__

Description
===========

The TypoScript of the extension can be loaded via site sets now. The
extension provides the following site sets:

*  ``extcode/cart-pdf`` (Shopping Cart - Cart Pdf): general configuration
*  ``extcode/cart-pdf-order-pdf`` (Shopping Cart - Cart Pdf - Order PDF)
*  ``extcode/cart-pdf-invoice-pdf`` (Shopping Cart - Cart Pdf - Invoice PDF)
*  ``extcode/cart-pdf-delivery-pdf`` (Shopping Cart - Cart Pdf - Delivery PDF)

The sets for the documents depend on ``extcode/cart-pdf``, so it is enough
to add the sets of the required documents to the site configuration, e.g.:

.. code-block:: yaml

   dependencies:
     - extcode/cart-pdf-order-pdf
     - extcode/cart-pdf-invoice-pdf

The static TypoScript templates are still available and include the same
TypoScript, so existing installations do not need to change anything.

.. index:: TypoScript
