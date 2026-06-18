<?php

declare(strict_types=1);

/**
 * Example smoke runner for the TCPDF compatibility facade.
 *
 * Executes the example scripts headless and applies the "Runs Successfully"
 * criteria from PLAN_WRAPPER.md:
 *   1. exit code 0 and no PHP warnings/notices/deprecations on stderr;
 *   2. the produced PDF is non-empty and parses (pdfinfo).
 *
 * The captured PDF for example_NNN.php is saved to target/examples/example_NNN.pdf
 * and a machine-readable report is written to target/report/example_smoke.json.
 *
 * Usage:
 *   php scripts/example_smoke.php           # run all examples
 *   php scripts/example_smoke.php 1 5 48    # run a subset by number
 *
 * Exit code: 0 when every executed example passes, 1 otherwise.
 *
 * @package com.tecnick.tcpdf
 */

error_reporting(E_ALL);

/**
 * Examples expected to fail under the declared breaking changes
 * (see "Breaking Changes" in PLAN_WRAPPER.md): name => reason.
 */
const TCPDF_SMOKE_XFAIL = [];

/**
 * Run a single example and apply the pass criteria.
 *
 * @return array{name: string, pass: bool, checks: array<string, string>}
 */
function tcpdf_smoke_run(string $exdir, string $script, string $pdfpath): array
{
    $checks = [];
    $errpath = $pdfpath . '.stderr';
    $cmdline = implode(' ', [
        'cd ' . escapeshellarg($exdir) . ' &&',
        escapeshellarg(PHP_BINARY),
        '-d error_reporting=32767', // E_ALL
        '-d display_errors=stderr',
        '-d xdebug.mode=off',
        '-d memory_limit=1G',
        escapeshellarg($script),
        '> ' . escapeshellarg($pdfpath),
        '2> ' . escapeshellarg($errpath),
    ]);

    $unused = [];
    $exit = 1;
    exec($cmdline, $unused, $exit);
    $stderr = is_file($errpath) ? (string) file_get_contents($errpath) : '';
    if (is_file($errpath)) {
        unlink($errpath);
    }

    $pass = true;

    if ($exit !== 0) {
        $checks['exit'] = 'FAIL: exit code ' . $exit;
        $pass = false;
    } else {
        $checks['exit'] = 'ok';
    }

    if ($stderr !== '') {
        $checks['stderr'] = 'FAIL: ' . trim(substr($stderr, 0, 500));
        $pass = false;
    } else {
        $checks['stderr'] = 'ok';
    }

    $pdf = is_file($pdfpath) ? (string) file_get_contents($pdfpath, false, null, 0, 8) : '';
    if ($pdf === '' || !str_starts_with($pdf, '%PDF-')) {
        $checks['pdf'] = 'FAIL: output is empty or not a PDF';
        $pass = false;
    } else {
        $checks['pdf'] = 'ok';
        $pdfinfo = tcpdf_smoke_pdfinfo($pdfpath);
        if ($pdfinfo === null) {
            $checks['pdfinfo'] = 'FAIL: pdfinfo cannot parse the output';
            $pass = false;
        } else {
            $checks['pdfinfo'] = 'ok: ' . $pdfinfo;
        }
    }

    return [
        'name' => basename($script),
        'pass' => $pass,
        'checks' => $checks,
    ];
}

/**
 * Parse a PDF with pdfinfo; returns "pages=N size=WxH" or null on failure.
 */
function tcpdf_smoke_pdfinfo(string $pdfpath): ?string
{
    $lines = [];
    $code = 1;
    exec('pdfinfo ' . escapeshellarg($pdfpath) . ' 2>/dev/null', $lines, $code);
    if ($code !== 0) {
        return null;
    }

    $out = implode("\n", $lines);
    $match = [];
    $pages = preg_match('/^Pages:\s+(\d+)$/m', $out, $match) === 1 ? $match[1] ?? '?' : '?';
    $match = [];
    $size = preg_match('/^Page size:\s+(.+)$/m', $out, $match) === 1 ? trim($match[1] ?? '?') : '?';

    return 'pages=' . $pages . ' size=' . $size;
}

$repodir = dirname(__DIR__);
$exdir = $repodir . '/examples';
$outdir = $repodir . '/target/examples';
$repdir = $repodir . '/target/report';
foreach ([$outdir, $repdir] as $dir) {
    if (is_dir($dir)) {
        continue;
    }

    if (!mkdir($dir, 0o775, true)) {
        fwrite(STDERR, 'Unable to create ' . $dir . "\n");
        exit(1);
    }
}

$scripts = glob($exdir . '/example_*.php');
if ($scripts === false || $scripts === []) {
    fwrite(STDERR, 'No example scripts found in ' . $exdir . "\n");
    exit(1);
}

sort($scripts, SORT_STRING);

$only = array_map(static fn(string $arg): string => str_pad($arg, 3, '0', STR_PAD_LEFT), array_slice($argv, 1));

/** @var array<string, string> $xfaillist */
$xfaillist = TCPDF_SMOKE_XFAIL;

$results = [];
$failed = 0;
foreach ($scripts as $script) {
    $name = basename($script, '.php');
    if ($only !== [] && !in_array(substr($name, -3), $only, true)) {
        continue;
    }

    $res = tcpdf_smoke_run($exdir, $script, $outdir . '/' . $name . '.pdf');
    $xfail = $xfaillist[$res['name']] ?? null;
    if ($xfail !== null) {
        // Expected failure under a declared breaking change: the gate stays
        // green only while the example still fails for the documented reason.
        $res['xfail'] = $xfail;
        if ($res['pass']) {
            $res['pass'] = false;
            $status = 'XPASS';
            ++$failed;
            $detail = 'unexpected pass: re-evaluate the breaking change entry';
        } else {
            $res['pass'] = true;
            $status = 'XFAIL';
            $detail = $xfail;
        }

        $results[] = $res;
        echo str_pad($res['name'], 20) . ' ' . $status . '  [' . $detail . ']' . "\n";
        continue;
    }

    $results[] = $res;
    $status = $res['pass'] ? 'PASS' : 'FAIL';
    if (!$res['pass']) {
        ++$failed;
        $detail = implode('; ', array_filter($res['checks'], static fn(string $msg): bool => str_starts_with(
            $msg,
            'FAIL',
        )));
    } else {
        $detail = $res['checks']['pdfinfo'] ?? '';
    }

    echo str_pad($res['name'], 20) . ' ' . $status . ($detail !== '' ? '  [' . $detail . ']' : '') . "\n";
}

$total = count($results);
$passed = $total - $failed;
echo "\n" . 'example-smoke: ' . $passed . '/' . $total . ' passed' . "\n";

$json = json_encode([
    'passed' => $passed,
    'total' => $total,
    'results' => $results,
], JSON_PRETTY_PRINT);

file_put_contents($repdir . '/example_smoke.json', ($json === false ? '{}' : $json) . "\n");

exit($failed === 0 ? 0 : 1);
