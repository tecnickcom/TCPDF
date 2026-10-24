# Security Policy

This policy covers the `tecnickcom/tcpdf` package.

**The entire TCPDF package is deprecated.** The maintained successor is [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf), and every TCPDF version should be treated as a migration source, not a destination.

TCPDF 7.x is a compatibility facade: the public TCPDF API is preserved, and PDF generation is delegated to `tc-lib-pdf`. TCPDF 6.x and earlier are the legacy monolithic engine.

## Supported Versions

| Version | Security fixes | Notes |
|---|---|---|
| 7.x | ⚠️ Maintenance only | Deprecated; security and critical compatibility fixes only, no new features |
| 6.x | ❌ No | Deprecated and unmaintained; no security fixes |
| < 6.x | ❌ No | End of life |

## Reporting a Vulnerability

Do not open a public GitHub issue for security vulnerabilities.

For TCPDF **7.x**, report privately through [GitHub private vulnerability reporting](https://github.com/tecnickcom/TCPDF/security/advisories/new) or to [info@tecnick.com](mailto:info@tecnick.com). Include the TCPDF version, the PHP version, and a minimal reproduction script.

Because rendering is delegated, many defects reported against 7.x originate in the `tc-lib-*` libraries. Such reports are handled in the corresponding upstream repository, and the advisory is issued there.

For TCPDF **6.x and earlier**, vulnerability reports are not accepted and will not be triaged, patched, or assigned an advisory.

## TCPDF 6.x and Earlier

**TCPDF 6.x is deprecated, unmaintained, and receives no security fixes.**

No security patches, advisories, or CVE remediations will be issued for the 6.x series or earlier. Known and unknown vulnerabilities in that code base remain unfixed.

Using TCPDF 6.x in production constitutes [CWE-1104: Use of Unmaintained Third Party Components](https://cwe.mitre.org/data/definitions/1104.html).

### Structural Vulnerability Classes

TCPDF 6.x contains recurring vulnerability classes that are inherent to its design. Individual reports in these classes have been patched over the years, and equivalent issues keep reappearing: the exposure comes from the public API contract, not from isolated defects. Removing these classes requires changing documented behavior that existing applications depend on, so they cannot be permanently fixed within 6.x without breaking backward compatibility.

The table below describes structural exposure in 6.x. It is not an inventory of specific known defects. The last column states how the same class is handled in 7.x.

| Class | CWE | Origin in TCPDF 6.x | Handling in TCPDF 7.x |
|---|---|---|---|
| Server-side request forgery | [CWE-918](https://cwe.mitre.org/data/definitions/918.html) | Images, fonts, and other resources are fetched from caller-supplied URLs through cURL and stream functions. | Remote loading is disabled by default; hosts must be listed in `K_ALLOWED_HOSTS`. |
| Path traversal and local file disclosure | [CWE-22](https://cwe.mitre.org/data/definitions/22.html), [CWE-73](https://cwe.mitre.org/data/definitions/73.html) | Filesystem paths supplied by the caller are opened directly for images, fonts, ICC profiles, and templates, and for the `Output()` file destinations. | Local reads, and the `Output()` `F`/`FI`/`FD` destination, are restricted to a `realpath()` allowlist of trusted roots, widened only by `K_ALLOWED_PATHS`. Image masks follow the same allowlist. Resources referenced by HTML, CSS, and SVG markup use it without the system temp directory. The written file name is sanitized by the engine. |
| Unsafe stream wrappers and object injection | [CWE-502](https://cwe.mitre.org/data/definitions/502.html) | Path arguments reach PHP file APIs, so wrapper schemes such as `phar://` are reachable from caller-controlled strings. | Constrained by the same path allowlist; `setAllowLocalFiles()` no longer widens access. |
| XML external entities and entity expansion | [CWE-611](https://cwe.mitre.org/data/definitions/611.html), [CWE-776](https://cwe.mitre.org/data/definitions/776.html) | SVG input is processed by an XML parser. | SVG parsing is performed by the engine; report against `tc-lib-pdf`. |
| Uncontrolled resource consumption | [CWE-400](https://cwe.mitre.org/data/definitions/400.html), [CWE-674](https://cwe.mitre.org/data/definitions/674.html), [CWE-1333](https://cwe.mitre.org/data/definitions/1333.html) | HTML, CSS, and SVG are parsed with recursive descent and extensive regular expressions over unbounded input. | Parsing is performed by the engine; remote downloads are capped by `K_MAX_REMOTE_SIZE`. |
| Unsafe parsing of binary input | [CWE-20](https://cwe.mitre.org/data/definitions/20.html) | Font, image, and barcode data are decoded with minimal validation of lengths and offsets. | Decoding is performed by the `tc-lib-*` libraries; report against them. |
| Broken or risky cryptography | [CWE-327](https://cwe.mitre.org/data/definitions/327.html) | The document encryption API still exposes the legacy RC4 and MD5 based PDF security handlers. | `setProtection()` modes 0 and 1 remain available for compatibility; AES modes 2 and 3 are recommended. |
| Insecure temporary files | [CWE-377](https://cwe.mitre.org/data/definitions/377.html) | Intermediate data is written to a shared cache directory configured by a global constant. | The legacy disk cache is an intentional no-op. |
| Injection into generated documents | [CWE-94](https://cwe.mitre.org/data/definitions/94.html), [CWE-116](https://cwe.mitre.org/data/definitions/116.html) | Embedded JavaScript, annotations, links, and form actions are assembled from caller-supplied strings. | Unchanged: escaping caller-supplied strings remains the responsibility of the application. |
| Insecure defaults | [CWE-1188](https://cwe.mitre.org/data/definitions/1188.html) | Behavior is driven by global `K_*` constants whose permissive defaults enable remote and filesystem access. | Defaults deny remote reads and restrict local reads to the built-in allowlist. |

### Required Action

Migrate to [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf).

Where an immediate migration is not feasible, upgrading to TCPDF 7.x is an interim step: it keeps the same public API and restores a supported security channel, but 7.x is itself deprecated and is not an end state.

Until the upgrade is complete, treat every TCPDF 6.x deployment as an accepted risk:

- Never pass untrusted input (HTML, images, fonts, URLs, file paths) to TCPDF.
- Run PDF generation in an isolated process with no network access and restricted filesystem permissions.
- Disable remote resource fetching and PHP stream wrappers where possible.
- Record the exposure in the project risk register.

## Hardening TCPDF 7.x

TCPDF 7.x is deprecated and receives security fixes only. The mitigations above are defaults, not guarantees. While migration to `tc-lib-pdf` is pending, and in particular when rendering untrusted input:

- Leave `K_ALLOWED_HOSTS` empty unless remote resources are required, and list exact host names when they are.
- Keep `K_ALLOWED_PATHS` as narrow as the deployment allows, and lower `K_MAX_REMOTE_SIZE` to the expected asset size. Do not list the system temp directory when rendering untrusted markup.
- Use AES encryption (`setProtection()` modes 2 or 3) instead of the legacy RC4 modes.
- Do not build embedded JavaScript, annotations, links, or form actions from unescaped user input.
- Run PDF generation in an isolated process with restricted filesystem and network permissions.

See [Resource Loading Security](README.md#resource-loading-security) for the full configuration reference.
