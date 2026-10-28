<?php
/* Static check: bind_param() type length vs number of bound variables
 * Usage: php test_bind_params.php [projectDir]
 */
require_once __DIR__ . '/bootstrap.php';

$dir = ($argv[1] ?? null) ?: dirname(__DIR__);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
$issues = 0;
foreach ($rii as $file) {
    if (strtolower($file->getExtension()) !== 'php') continue;
    $src = file_get_contents($file->getPathname());
    if (preg_match_all('/->bind_param\(\s*([\'"])([a-zA-Z]+)\1\s*(,.*?)\)\s*;/is', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $types = strlen($hit[2]);
            $args = trim($hit[3], ', ');
            if ($args === '') $count = 0;
            else $count = preg_match_all('/\$[a-zA-Z_][a-zA-Z0-9_]*(\[\s*\'[a-z_]+\'\s*\]|\s*\.\s*\$[a-zA-Z_][a-zA-Z0-9_]*)*/i', $args);
            if ($types !== $count) {
                $rel = str_replace($dir . '/', '', $file->getPathname());
                $line = substr_count(substr($src, 0, strpos($src, $hit[0])), "\n") + 1;
                echo "MISMATCH $rel:$line types=$types vars=$count\n";
                $issues++;
            }
        }
    }
}
echo $issues === 0 ? "ALL bind_param CALLS CONSISTENT\n" : "TOTAL ISSUES: $issues\n";

