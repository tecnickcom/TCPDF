# TCPDF

> **⚠️ ARCHIVED LEGACY VERSION — NO UPDATES WILL BE PROVIDED**
>
> This is an archived branch of TCPDF. Feature development and updates have been discontinued.
> For new projects and migrating existing code, use the modern [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf) library.

[![Latest Stable Version](https://poser.pugx.org/tecnickcom/tcpdf/version)](https://packagist.org/packages/tecnickcom/tcpdf)
[![License](https://poser.pugx.org/tecnickcom/tcpdf/license)](https://packagist.org/packages/tecnickcom/tcpdf)
[![Downloads](https://poser.pugx.org/tecnickcom/tcpdf/downloads)](https://packagist.org/packages/tecnickcom/tcpdf)
[![Sponsor on GitHub](https://img.shields.io/badge/sponsor-github-EA4AAA.svg?logo=githubsponsors&logoColor=white)](https://github.com/sponsors/tecnickcom)

If this project is useful to you, please consider [supporting development via GitHub Sponsors](https://github.com/sponsors/tecnickcom).

---

## Status: Archived & No Longer Maintained

This repository is an archived branch of TCPDF and **will not receive any further updates** or feature development.

Active feature development has moved to [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf), the modern and modular successor.

### About the Main Branch

The **main branch** of this repository contains a refactored version of TCPDF that uses `tc-lib-pdf` as its backend. This version provides:

- **Drop-in compatibility layer**: wraps `tc-lib-pdf` to maintain practical backward compatibility with existing TCPDF code
- **Modern architecture**: leverages the modular and extensible `tc-lib-pdf` foundation
- **Breaking changes**: some changes are necessary and may require updates to existing implementations

For active development and complete documentation, **refer to the [main branch](https://github.com/tecnickcom/TCPDF/tree/main)** of this repository.

### Migration Guide

If you are using this archived legacy branch, consider migrating to:

1. **[Main branch](https://github.com/tecnickcom/TCPDF/tree/main)** — if you want to stay within the TCPDF API while using the modern `tc-lib-pdf` backend
2. **[tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf)** — for new projects or when you're ready to adopt the modern PDF library directly

### Why Migrate

- **Modern architecture**: modular libraries and cleaner component boundaries improve maintainability
- **Better extensibility**: new features are easier to add without patching a monolithic legacy core
- **Stronger tooling fit**: modern package structure works better with static analysis, CI, and automated tests
- **Lower long-term risk**: reduces technical debt tied to legacy APIs and supports ongoing PHP ecosystem evolution
- **Improved delivery speed**: teams can implement and ship new PDF capabilities with less friction

Migration requires planning and regression checks to preserve rendering parity for existing documents, but the investment pays off with reduced maintenance burden.

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

- PHP 7.1 or later
- `ext-curl`

Optional extensions for richer output in some workflows: `gd`, `zlib`, `imagick`.

---

## Third-Party Fonts

This library may include third-party font files released under different licenses.

PHP metadata files under [fonts](fonts) are covered by the TCPDF license (GNU LGPL v3). They contain font metadata and can also be generated using TCPDF font utilities.

Original binary TTF files are renamed for compatibility and compressed with PHP `gzcompress` (the `.z` format).

| Prefix | Source | License |
|---|---|---|
| `free*` | [GNU FreeFont](https://www.gnu.org/software/freefont/) | GNU GPL v3 |
| `pdfa*` | Derived from GNU FreeFont | GNU GPL v3 |
| `dejavu*` | [DejaVu Fonts](http://dejavu-fonts.org) | Bitstream/DejaVu terms |
| `ae*` | [Arabeyes.org](http://projects.arabeyes.org/) | GNU GPL v2 |

For full details, see the bundled notices in the corresponding subdirectories under [fonts](fonts).

---

## ICC Profile

TCPDF includes `sRGB.icc` from the Debian [`icc-profiles-free`](https://packages.debian.org/source/stable/icc-profiles-free) package.

---

## Contact

Nicola Asuni <info@tecnick.com>
