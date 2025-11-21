# CartPDF

## Description

Cart is a small but powerful extension which "solely" adds a shopping cart to your TYPO3 installation.
CartPdf allows you to generate attractive PDFs. Whether order confirmations, invoices, delivery notes,
or even your own types. The EventListener integrates itself into the ordering process so that the
generated files are sent as email attachments.

## 2. Installation

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is by using [Composer][2]. In your Composer based TYPO3 project root, just do `composer require extcode/cart-pdf`.

#### Installation as extension from TYPO3 Extension Repository (TER)

An installation from TER is not possible because tecnickcom/tcpdf will be installed as a dependency.
Installing  external packages is not provided by the extension manager.

### 2.2 Update and Upgrade

**Attention**, Before updating to a new minor version or upgrading to a new major version, be sure to check the
changelog section in the documentation.
Sometimes minor versions also result in minor adjustments to own templates or configurations.

## 3. Administration

## 3.1 Compatibility and supported Versions

| Cart Pdf | TYPO3 | PHP       | Support/Development                  |
|----------|-------|-----------|--------------------------------------|
| 7.x.x    | 12.4  | 8.1+      | Bugfixes, Security Updates           |
| 5.x.x    | 10.4  | 7.2 - 7.4 |                                      |
| 4.x.x    | 9.5   | 7.2 - 7.4 |                                      |
| 3.x.x    | 8.7   | 7.0 - 7.4 |                                      |

If you need extended support for features and bug fixes outside of the currently supported versions,
we are happy to offer paid services.

### 3.2. Changelog

Please have a look into the [official extension documentation in changelog chapter](https://docs.typo3.org/p/extcode/cart-pdf/main/en-us/Changelog/Index.html)

### 3.3. Release Management

News uses **semantic versioning** which basically means for you, that
- **bugfix updates** (e.g. 1.0.0 => 1.0.1) just includes small bugfixes or security relevant stuff without breaking changes.
- **minor updates** (e.g. 1.0.0 => 1.1.0) includes new features and smaller tasks without breaking changes.
- **major updates** (e.g. 1.0.0 => 2.0.0) breaking changes wich can be refactorings, features or bugfixes.

## 4. Sponsoring

* Ask for an invoice.
* [GitHub Sponsors](https://github.com/sponsors/extcode)
* [PayPal.Me](https://paypal.me/extcart)

[1]: https://docs.typo3.org/typo3cms/extensions/cart_pdf/
[2]: https://getcomposer.org/
