# TCPDF (DEPRECATED → use [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf))

> Legacy PDF API for PHP, implemented as a **compatibility facade** over the modern
> [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf) engine. **Deprecated** and
> maintained for existing integrations.

[![Latest Stable Version](https://poser.pugx.org/tecnickcom/tcpdf/version)](https://packagist.org/packages/tecnickcom/tcpdf)
[![License](https://poser.pugx.org/tecnickcom/tcpdf/license)](https://packagist.org/packages/tecnickcom/tcpdf)
[![Downloads](https://poser.pugx.org/tecnickcom/tcpdf/downloads)](https://packagist.org/packages/tecnickcom/tcpdf)

[![Sponsor on GitHub](https://img.shields.io/badge/sponsor-github-EA4AAA.svg?logo=githubsponsors&logoColor=white)](https://github.com/sponsors/tecnickcom)

If TCPDF and tc-lib-pdf are valuable to your work, please consider supporting ongoing development with a sponsorship. Financial support helps fund maintenance, bug fixes, documentation, testing infrastructure, and long-term improvements.

You can sponsor the project here: [https://github.com/sponsors/tecnickcom](https://github.com/sponsors/tecnickcom)

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

## Architecture: Compatibility Facade over tc-lib-pdf

Starting with this version, the `TCPDF` class no longer contains its own PDF engine.
It is a **compatibility facade**: every public TCPDF method is a thin wrapper that
delegates the actual PDF generation to the modern `tecnickcom/tc-lib-pdf` engine
(`\Com\Tecnick\Pdf\Tcpdf`), while a small internal state layer reproduces the legacy
stateful cursor and page model (current X/Y, margins, fonts, colors, automatic page
breaks, headers/footers).

What this means in practice:

- **The public API is unchanged.** All 291 public method signatures (names, parameters,
  defaults) are identical to legacy TCPDF; existing integrations keep calling `new TCPDF(...)`,
  `AddPage()`, `SetFont()`, `Cell()`, `writeHTML()`, `Output()` exactly as before.
- **Rendering is done by the modern engine.** Text layout, HTML/CSS, fonts, graphics,
  barcodes, encryption, signatures and output generation come from the `tc-lib-*` libraries.
- **Per-method delegation status is documented.** See [MAPPING.md](MAPPING.md) for the
  status of every public method (`delegated`, `adapter`, `shim`, `intentional-noop`,
  `blocked`) with notes; the table is machine-verified against the class.
- **Output is structurally equivalent, not byte-identical.** Documents render with the
  same page sizes and content, but the modern engine's line-breaking and font metrics can
  differ slightly from the legacy implementation (long flowing documents may paginate one
  page earlier or later).
- **Some legacy behaviors are intentionally not reproduced.** A few features are dropped
  or changed where the modern engine's model takes precedence (legacy font definitions,
  EPS/AI vector import, always-on stream compression, policy-based local file access,
  assorted no-ops). See [Breaking Changes](#breaking-changes) below and the per-method
  notes in [MAPPING.md](MAPPING.md) for the full list.

---

## Deprecation Notice

TCPDF is **deprecated** and in **maintenance-only mode**.

Active feature development has moved to [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf), the modern and modular successor.

For new projects, use `tecnickcom/tc-lib-pdf`. This repository remains available for legacy systems and critical compatibility fixes.

### Migration Path

- New projects: install `tecnickcom/tc-lib-pdf`.
- Existing TCPDF users: keep TCPDF for current production workloads and migrate in phases.
- Teams seeking modern architecture, Composer-first design, and stronger type-safety should prioritize `tc-lib-pdf`.

### Migrating Font Assets

TCPDF has migrated font loading to the tc-lib font stack (see "Breaking Changes" below).

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
- Legacy PHP font descriptors (`fontname.php` + `fontname.z`) are **no longer supported**
  (see "Breaking Changes" below); convert the original TTF/OTF with the
  `tc-lib-pdf-font` importer instead.

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

---

## Breaking Changes

The facade favors the modern engine model over bug-for-bug legacy emulation in the
following areas. Each is a deliberate, documented contract change:

1. **Font model.** Fonts are resolved exclusively through the tc-lib-pdf-font stack:
   JSON definition files discovered under `K_PATH_FONTS`
   (`vendor/tecnickcom/tc-lib-pdf-font/target/fonts/`, generated by `make fonts`).
   The legacy TCPDF font definition format (`fontname.php` + `fontname.z` /
   `fontname.ctg.z`) is **not supported** and is not converted at runtime:
   - `SetFont()`/`AddFont()` accept families known to the tc-lib font stack
     (core fonts, DejaVu, FreeFont, CID-0, ...) or definition files in the tc-lib
     JSON format via the font-file parameter.
   - Legacy-only bundled fonts (e.g. `aefurat`, `aealarabiya`) are unavailable;
     requesting them throws a font exception. Use a tc-lib font with equivalent
     coverage (e.g. `freeserif`/`dejavusans` for Arabic) or import the original
     TTF/OTF with the `tc-lib-pdf-font` importer.
   - Font subsetting, kerning and metrics follow the tc-lib implementation.

   See "Migrating Font Assets" above for the step-by-step migration procedure.
2. **Stream compression is always on.** `setCompression(false)` is a no-op; the engine
   always compresses content streams.
3. **EPS/AI vector import is dropped.** The modern engine has no PostScript interpreter,
   so `ImageEps()` ignores EPS/AI input. **Convert EPS/AI artwork to SVG**
   (e.g. `inkscape file.eps --export-filename=file.svg`) and use `ImageSVG()` instead.
   As a convenience, `ImageEps()` dispatches SVG and raster file names to the modern paths.
4. **RC4 encryption is legacy-only.** `setProtection()` modes 0/1 still work, but the
   engine deprecates RC4; AES modes (2/3) are recommended. `setProtection()` must be
   called before the first page is added.
5. **Resource loading is policy-based.** The engine restricts where external resources
   (images, fonts, SVG, imported PDFs) may be loaded from: local reads are limited to an
   allowlist of trusted directories and remote (HTTP/HTTPS) reads are disabled by default.
   The legacy `setAllowLocalFiles()` toggle no longer widens access; the policy is driven
   by configuration constants instead (see [Resource Loading Security](#resource-loading-security)).

Smaller deliberate no-ops (disk caching, `setDocInfoUnicode()`, header XObject template
caching, vector-image rasterization toggles, ...) are listed with their reasons in
[MAPPING.md](MAPPING.md).

### Resource Loading Security

External resources are fetched through the sandboxed file helper provided by
`tc-lib-pdf` / `tc-lib-file`. The sandbox enforces two independent allowlists, both
configurable via `define()` constants (read by `tcpdf_autoconfig.php`, overridable in
`config/tcpdf_config.php` or before the autoconfig runs):

| Constant | Type | Default | Purpose |
| --- | --- | --- | --- |
| `K_ALLOWED_PATHS` | `string[]` | `[]` | Extra trusted **local** directory prefixes, **merged on top of** the built-in defaults. |
| `K_ALLOWED_HOSTS` | `string[]` | `[]` | Trusted **remote** host names that enable HTTP/HTTPS loading. Empty keeps remote loading **disabled**. |
| `K_MAX_REMOTE_SIZE` | `int` | `52428800` | Byte cap for a single remote download (50 MiB). |
| `K_CURLOPTS` | `array` | `[]` | Extra `CURLOPT_* => value` pairs merged over the cURL defaults. |

**Local reads.** The built-in allowlist always covers the system temp directory,
`K_PATH_MAIN`, the bundled `vendor/tecnickcom/` directory, the current working directory,
`K_PATH_FONTS`, `K_PATH_IMAGES` and the running script's directory. `K_ALLOWED_PATHS`
only ever *widens* this set — paths are resolved with `realpath()`, so non-existent or
unresolvable entries are silently ignored and traversal/symlink tricks collapse to their
canonical prefix. There is no way to read below the built-in roots.

**Remote reads.** Remote URL loading is **off by default** — the single most important
defense against SSRF when rendering untrusted HTML/markup. To opt in, list the exact
host names you trust in `K_ALLOWED_HOSTS`. TLS certificate verification and redirect
handling are enforced upstream and **cannot** be relaxed through `K_CURLOPTS`.

```php
// Enable downloads from two trusted CDNs, cap them at 10 MiB, and add a custom timeout.
define('K_ALLOWED_HOSTS', ['cdn.example.com', 'assets.example.org']);
define('K_MAX_REMOTE_SIZE', 10 * 1024 * 1024);
define('K_CURLOPTS', [CURLOPT_TIMEOUT => 15]);
// Allow reading shared assets from outside the install tree.
define('K_ALLOWED_PATHS', ['/var/www/shared/assets/']);
```

Document **encryption** is a separate concern: `setProtection()` (item 4 above) controls
the PDF permission flags and password/public-key encryption and is unaffected by these
resource-loading constants.

---

## Requirements

- PHP 8.2 or later
- `ext-curl`

Optional extensions for richer output in some workflows: `gd` (automatic raster format conversion), `zlib`.

---

## Development & Quality Assurance

This repository ships a real validation harness:

| Command | Purpose |
|---|---|
| `make deps` | Install Composer dependencies, tooling, and initialize tc-lib font assets |
| `make qa` | Full gate: `mago` lint + static analysis + PHPUnit suite |
| `make test` | Run the PHPUnit suite ([test/](test/)) |
| `make smoke` | Run all 68 example scripts headless and verify the produced PDF documents |
| `make inventory` | Regenerate the public method inventory reports |
| `make mapping` | Verify the delegation map and regenerate [MAPPING.md](MAPPING.md) |

The example smoke runner ([scripts/example_smoke.php](scripts/example_smoke.php)) requires
`pdfinfo` (poppler-utils) and treats any warning, notice, or deprecation as a failure.
Examples that exercise a declared breaking change can be tracked as expected failures with
a documented reason (currently none: all 68 examples pass).

---

## Third-Party Fonts

Third-party bundled font assets are provided through `tecnickcom/tc-lib-pdf-font` under `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/`.

TCPDF no longer ships a repository-local `fonts/` directory.

For full details, see the bundled notices shipped by `tecnickcom/tc-lib-pdf-font`.

---
