# Contributing to TCPDF

Thank you for your interest in contributing to **TCPDF**. TCPDF is a mature, widely deployed PDF engine for PHP that is now in **maintenance mode**: it receives bug fixes and security fixes, but no new features. For new projects, please use [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf), the modern replacement.

Please take a moment to read this guide before opening an issue or pull request.

> **Pull requests are restricted to project collaborators.** If you are not a collaborator, please [open an issue](https://github.com/tecnickcom/TCPDF/issues) instead of a pull request, describing the bug or feature in detail. A maintainer will review it and take it from there.

---

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Getting Started](#getting-started)
- [Reporting a Bug](#reporting-a-bug)
- [Submitting a Bug Fix](#submitting-a-bug-fix)
- [Proposing a New Feature](#proposing-a-new-feature)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Guidelines](#pull-request-guidelines)
- [Commit Message Guidelines](#commit-message-guidelines)

---

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/). By participating you agree to abide by its terms. Please report unacceptable behaviour to [info@tecnick.com](mailto:info@tecnick.com).

---

## Security Vulnerabilities

**Do not open a public GitHub issue for security vulnerabilities.**  
Please report them privately to [info@tecnick.com](mailto:info@tecnick.com).

---

## Getting Started

### Requirements

- PHP **>= 8.2** with the `curl` extension
- [Composer](https://getcomposer.org/) v2
- `make`, `git`

### Local setup

```bash
git clone https://github.com/tecnickcom/TCPDF.git
cd TCPDF
make buildall
```

To verify everything is working after a change:

```bash
make qa
```

This runs linting, static analysis, and the unit-test suite.

---

## Reporting a Bug

Before opening an issue:

1. **Report security issues privately** (see [Security Vulnerabilities](#security-vulnerabilities)), do not file a public issue.
2. **Search [existing issues](https://github.com/tecnickcom/TCPDF/issues)** to avoid duplicates.

If no existing issue matches, [open a new one](https://github.com/tecnickcom/TCPDF/issues/new) and include:

- A **clear title and description** of the problem.
- The **library version** (the `VERSION` file or `composer show tecnickcom/tcpdf`) and PHP version.
- A **minimal, self-contained reproduction**: a short PHP script or a failing PHPUnit test case is ideal.
- **Expected vs. actual behaviour**: what you expected to happen and what actually happened.
- Any relevant **stack trace or error output**.

The more precise and reproducible the report, the faster it can be triaged and fixed.

---

## Submitting a Bug Fix

> Only project collaborators can open pull requests. If you are not a collaborator, please [open an issue](https://github.com/tecnickcom/TCPDF/issues/new) describing the bug in detail (see [Reporting a Bug](#reporting-a-bug)). A maintainer will take it from there.

Collaborators preparing a fix:

1. Create a branch from `main`:
   ```bash
   git checkout -b fix/short-description-of-bug
   ```
2. Make your changes, following the [Coding Standards](#coding-standards) below.
3. Add or update unit tests to cover the changes.
4. Run the full quality-assurance suite locally and ensure it passes:
   ```bash
   make qa
   ```
5. Commit your changes (see [Commit Message Guidelines](#commit-message-guidelines)).
6. Open a pull request against `main`, describing the problem and your solution and referencing the related issue number (e.g. `Fixes #123`).

---

## Proposing a New Feature

TCPDF is in maintenance mode and **no longer accepts new features**: only bug fixes and security fixes are merged.

If you need new functionality, please use or contribute to [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf), the modern replacement. If you are unsure whether a change qualifies as a fix, [open an issue](https://github.com/tecnickcom/TCPDF/issues/new) and ask before writing any code.

---

## Development Workflow

The `Makefile` exposes all common development tasks:

| Command | Description |
|---------|-------------|
| `make qa` | Run linting, static analysis, and tests |
| `make test` | Run PHPUnit with code coverage |
| `make lint` | Check coding standards and run static analysis |
| `make format` | Auto-format the code |
| `make buildall` | Install dependencies and build |
| `make clean` | Remove `vendor/` and `target/` directories |
| `make server` | Start the built-in PHP development server for the examples |

Run `make help` to see the full list of available targets.

---

## Coding Standards

- Match the conventions of the surrounding code. TCPDF is long-lived legacy code, so consistency with the existing file matters more than introducing new patterns.
- Run `make format` to auto-format the code and `make lint` to check style and run static analysis (both use [Mago](https://github.com/carthage-software/mago)).
- The library is autoloaded via a classmap (`tcpdf.php` and `config/`); tests live under `test/`.
- Keep changes minimal and focused, and avoid introducing new external dependencies.

---

## Testing

Tests are written with [PHPUnit](https://phpunit.de/) and live in `test/`.

```bash
# Run the full test suite with coverage
make test

# Run a specific test file
XDEBUG_MODE=coverage ./vendor/bin/phpunit test/TcpdfCellTest.php
```

Every bug fix must be accompanied by a regression test that fails before the fix and passes after. Coverage reports are generated under `target/`.

---

## Pull Request Guidelines

> Opening pull requests is restricted to project collaborators. If you are an external contributor, please [open an issue](https://github.com/tecnickcom/TCPDF/issues/new) describing the problem in detail instead.

- **Sign the Contributor License Agreement (CLA).** On your first pull request the CLA Assistant bot will comment with a link to sign; the PR cannot be merged until the CLA is signed.
- Target the `main` branch.
- Keep PRs focused: one fix per PR.
- Limit changes to bug fixes and security fixes; new features belong in [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf).
- Ensure `make qa` passes locally before opening the PR.
- Do not bump the version number in your PR; that is handled by the maintainer at release time.
- Be responsive to review feedback; stale PRs may be closed after an extended period of inactivity.

---

## Commit Message Guidelines

Use concise, imperative-mood commit messages:

```
fix: correct cell border rendering for RTL text
test: add regression test for #123
docs: update CONTRIBUTING workflow
refactor: simplify font cache lookup
chore: update dependency constraints
```

Prefix tags: `fix`, `test`, `docs`, `refactor`, `chore`, `ci`.  
Reference issues where relevant: `fix: correct X (closes #42)`.

---

## Questions?

If you have a question that is not covered here, feel free to [open an issue](https://github.com/tecnickcom/TCPDF/issues) or contact the maintainer at [info@tecnick.com](mailto:info@tecnick.com).
