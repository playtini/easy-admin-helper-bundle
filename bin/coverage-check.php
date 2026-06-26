<?php

declare(strict_types=1);

/**
 * Minimal Clover line-coverage gate (no external dependency).
 *
 * Usage: php bin/coverage-check.php <clover.xml> <min-percent>
 * Exits non-zero when line coverage is below the given threshold.
 */

[$cloverFile, $minPercent] = [$argv[1] ?? null, (float)($argv[2] ?? 0)];

if ($cloverFile === null || !is_file($cloverFile)) {
    fwrite(STDERR, sprintf("Clover file not found: %s\n", (string)$cloverFile));
    exit(2);
}

$xml = simplexml_load_file($cloverFile);
if ($xml === false) {
    fwrite(STDERR, sprintf("Could not parse clover file: %s\n", $cloverFile));
    exit(2);
}

// Pick the aggregate <metrics> with the most statements (the project-level totals).
$statements = 0;
$covered = 0;
foreach ($xml->xpath('//metrics') ?: [] as $metrics) {
    $s = (int)$metrics['statements'];
    if ($s >= $statements) {
        $statements = $s;
        $covered = (int)$metrics['coveredstatements'];
    }
}

if ($statements === 0) {
    fwrite(STDERR, "No statements found in clover report.\n");
    exit(2);
}

$percent = round($covered / $statements * 100, 2);

if ($percent < $minPercent) {
    fwrite(STDERR, sprintf("Line coverage %.2f%% is below the required %.2f%%.\n", $percent, $minPercent));
    exit(1);
}

fwrite(STDOUT, sprintf("Line coverage %.2f%% meets the required %.2f%%.\n", $percent, $minPercent));
exit(0);
