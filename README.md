# TCPDF

> Legacy PDF engine for PHP. **Deprecated** and maintained for existing integrations.

[![Latest Stable Version](https://poser.pugx.org/tecnickcom/tcpdf/version)](https://packagist.org/packages/tecnickcom/tcpdf)
[![License](https://poser.pugx.org/tecnickcom/tcpdf/license)](https://packagist.org/packages/tecnickcom/tcpdf)
[![Downloads](https://poser.pugx.org/tecnickcom/tcpdf/downloads)](https://packagist.org/packages/tecnickcom/tcpdf)
[![Donate via PayPal](https://img.shields.io/badge/donate-paypal-87ceeb.svg)](https://www.paypal.com/donate/?hosted_button_id=NZUEC5XS8MFBJ)

If TCPDF helps your business, please consider supporting development via [PayPal](https://www.paypal.com/donate/?hosted_button_id=NZUEC5XS8MFBJ).

---

## Deprecation Notice

TCPDF is **deprecated** and in **maintenance-only mode**.

Active feature development has moved to [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf), the modern and modular successor.

For new projects, use `tecnickcom/tc-lib-pdf`. This repository remains available for legacy systems and critical compatibility fixes.

### Migration Path

- New projects: install `tecnickcom/tc-lib-pdf`.
- Existing TCPDF users: keep TCPDF for current production workloads and migrate in phases.
- Teams seeking modern architecture, Composer-first design, and stronger type-safety should prioritize `tc-lib-pdf`.

### Breaking Change: Font Asset Migration

TCPDF is migrating font loading to the tc-lib font stack.

- `tecnickcom/tc-lib-pdf` is the Composer entrypoint.
- Font assets are provided by `tecnickcom/tc-lib-pdf-font` and discovered under `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/`.
- Repository-shipped `fonts/` assets are removed; TCPDF now resolves bundled fonts from tc-lib assets.

Who is affected:

- Deployments that relied on local `fonts/` files without Composer dependencies.
- Applications with custom `K_PATH_FONTS` assumptions tied to a repository-relative fonts folder.
- Integrations that use custom or generated font definitions and expect PHP-only descriptor files.

How to migrate custom font usage:

1. Install dependencies with Composer.
2. Ensure tc-lib font assets are available in `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/`.
3. Keep using `SetFont()`/`AddFont()` from TCPDF, but validate that each custom family resolves from tc-lib assets or from your explicit font path.
4. Update deployment packaging so `vendor/` font assets are shipped in production.

Font generation procedure (Makefile):

1. Run `make deps` to install Composer dependencies and initialize tc-lib font assets.
2. Run `make fonts` to initialize fonts only when missing.
3. Run `make fonts-rebuild` to force a full font asset rebuild.

Expected generated asset sentinel:

- `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/core/helvetica.json`

Compatibility notes:

- TCPDF checks configured font paths and tc-lib font assets.
- JSON font descriptors from tc-lib are accepted by the TCPDF `AddFont()` path.
- Legacy PHP descriptors can still be used when explicitly provided via custom paths.

Example:

```php
require __DIR__.'/vendor/autoload.php';

// Optional: override only if you need a non-default path.
define('K_PATH_FONTS', __DIR__.'/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/');

$pdf->SetFont('helvetica', '', 11);
```

Safe migration checklist:

1. Require `tecnickcom/tc-lib-pdf` in Composer and install dependencies.
2. Confirm the font asset directory exists under `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/`.
3. Run your PDF smoke tests for headers, body text, bold/italic, RTL text, and Unicode text.
4. Verify no runtime path assumptions require repository `fonts/` files.
5. Remove legacy `K_PATH_FONTS` overrides that point to removed directories.
6. Re-run regression output comparisons on representative documents.

### Why Migrate to tc-lib-pdf

- Modern architecture: modular libraries and cleaner component boundaries improve maintainability.
- Better extensibility: new features are easier to add without patching a monolithic legacy core.
- Stronger tooling fit: modern package structure works better with static analysis, CI, and automated tests.
- Lower long-term risk: reduces technical debt tied to legacy APIs and supports ongoing PHP ecosystem evolution.
- Improved delivery speed: teams can implement and ship new PDF capabilities with less friction.

Migration still requires planning and regression checks to preserve rendering parity for existing documents.

### Future Compatibility Possibility

As a long-term possibility, TCPDF could be refactored to use `tc-lib-pdf` internally as a backend while preserving a practical level of backward compatibility for existing TCPDF integrations.

This is not part of a committed roadmap and there is no guarantee it will happen. It is documented here only as a potential direction that may be evaluated in the future.

---

## Overview

TCPDF is a pure-PHP library for generating PDF documents and barcodes directly in application code.

It has been widely used across many PHP stacks and still provides a complete feature set for text rendering, page composition, graphics, signatures, forms, and standards-oriented output.

| | |
|---|---|
| **Package** | `tecnickcom/tcpdf` |
| **Author** | Nicola Asuni <info@tecnick.com> |
| **License** | [GNU LGPL v3](https://www.gnu.org/copyleft/lesser.html) (see [LICENSE.TXT](LICENSE.TXT)) |
| **Website** | <http://www.tcpdf.org> |
| **Source** | <https://github.com/tecnickcom/TCPDF> |

---

## Features

### Text & Fonts
- UTF-8 Unicode and right-to-left (RTL) language support
- TrueTypeUnicode, OpenTypeUnicode v1, TrueType, OpenType v1, Type1, and CID-0 fonts
- Font subsetting
- Text hyphenation, stretching, spacing, and rendering modes (fill/stroke/clipping)
- Automatic line breaks, page breaks, and justification

### Layout & Content
- Standard and custom page formats, margins, and measurement units
- XHTML + CSS rendering, JavaScript, and forms
- Automatic headers and footers
- Multi-column mode and no-write page regions
- Bookmarks, named destinations, and table of contents
- Automatic page numbering, page groups, move/delete pages, and undo transactions

### Images, Graphics & Color
- Native JPEG, PNG, and SVG support
- Geometric drawing primitives and transformations
- Support for GD image formats (`GD`, `GD2`, `GD2PART`, `GIF`, `JPEG`, `PNG`, `BMP`, `XBM`, `XPM`)
- Additional formats via ImageMagick (when available)
- JPEG/PNG ICC profiles, grayscale/RGB/CMYK/spot colors, and transparencies

### Security, Standards & Advanced Output
- Encryption up to 256-bit and digital signature certifications
- PDF annotations (links, text, and file attachments)
- 1D and 2D barcode support (including CODE 128, EAN/UPC, Datamatrix, QR Code, PDF417)
- XObject templates and layers with object visibility controls
- PDF/A-1b support

---

## Requirements

- PHP 8.1 or later
- `ext-curl`

Optional extensions for richer output in some workflows: `gd`, `zlib`, `imagick`.

---

## Third-Party Fonts

Third-party bundled font assets are provided through `tecnickcom/tc-lib-pdf-font` under `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/`.

TCPDF no longer ships a repository-local `fonts/` directory.

For full details, see the bundled notices shipped by `tecnickcom/tc-lib-pdf-font`.

---

## ICC Profile

TCPDF uses the bundled `sRGB.icc.z` profile provided by `tecnickcom/tc-lib-pdf`.

---

## Contact

Nicola Asuni <info@tecnick.com>
