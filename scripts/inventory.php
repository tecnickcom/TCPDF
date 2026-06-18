<?php

declare(strict_types=1);

/**
 * Generate the method inventory for the TCPDF compatibility facade.
 *
 * Lists every public method of the legacy TCPDF class and of the
 * delegation target \Com\Tecnick\Pdf\Tcpdf, with full signatures.
 * Output is written to target/report/ as tab-separated text files.
 *
 * Usage: php scripts/inventory.php
 *
 * @package com.tecnick.tcpdf
 */

error_reporting(E_ALL);

require_once dirname(__DIR__) . '/tcpdf.php';

/**
 * Render a parameter signature fragment.
 */
function tcpdf_inventory_param(ReflectionParameter $par): string
{
    $out = '';
    $type = $par->getType();
    if ($type instanceof ReflectionNamedType || $type instanceof ReflectionUnionType) {
        $out .= (string) $type . ' ';
    }

    if ($par->isPassedByReference()) {
        $out .= '&';
    }

    if ($par->isVariadic()) {
        $out .= '...';
    }

    $out .= '$' . $par->getName();
    if ($par->isDefaultValueAvailable()) {
        $out .= ' = ' . str_replace("\n", '', var_export($par->getDefaultValue(), true));
    }

    return $out;
}

/**
 * Render the full signature of a method.
 */
function tcpdf_inventory_signature(ReflectionMethod $met): string
{
    $pars = array_map('tcpdf_inventory_param', $met->getParameters());
    $ret = '';
    $type = $met->getReturnType();
    if ($type instanceof ReflectionNamedType || $type instanceof ReflectionUnionType) {
        $ret = ': ' . (string) $type;
    }

    return $met->getName() . '(' . implode(', ', $pars) . ')' . $ret;
}

/**
 * Write the public method inventory of a class to a TSV file.
 *
 * @param class-string $class Fully qualified class name.
 *
 * @return int Number of public methods found.
 */
function tcpdf_inventory_write(string $class, string $outfile): int
{
    $ref = new ReflectionClass($class);
    $rows = [];
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $met) {
        $rows[$met->getName()] = implode("\t", [
            $met->getName(),
            $met->isStatic() ? 'static' : 'instance',
            $met->getDeclaringClass()->getName(),
            tcpdf_inventory_signature($met),
        ]);
    }

    ksort($rows, SORT_STRING | SORT_FLAG_CASE);
    $header = implode("\t", ['method', 'kind', 'declared_in', 'signature']) . "\n";
    file_put_contents($outfile, $header . implode("\n", $rows) . "\n");
    return count($rows);
}

$repodir = dirname(__DIR__);
$outdir = $repodir . '/target/report';
if (!is_dir($outdir) && !mkdir($outdir, 0o775, true)) {
    fwrite(STDERR, 'Unable to create ' . $outdir . "\n");
    exit(1);
}

$numtcpdf = tcpdf_inventory_write('TCPDF', $outdir . '/inventory_tcpdf.tsv');
$numtclib = tcpdf_inventory_write('\Com\Tecnick\Pdf\Tcpdf', $outdir . '/inventory_tclibpdf.tsv');

echo 'TCPDF public methods:               ' . $numtcpdf . "\n";
echo '\Com\Tecnick\Pdf\Tcpdf public methods: ' . $numtclib . "\n";
echo 'Inventory written to ' . $outdir . "\n";
