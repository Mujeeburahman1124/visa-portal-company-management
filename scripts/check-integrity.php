<?php
/**
 * Static integrity checker (read-only, no DB writes).
 *  1. Every `new App\Controllers\X()->method()` call in public/index.php resolves to a real class + public method.
 *  2. Every `require ... '/Views/...php'` in controllers points to an existing view file.
 * Run: php scripts/check-integrity.php   (exit code 1 when problems found)
 */
require_once dirname(__DIR__) . '/app/autoload.php';

$root = dirname(__DIR__);
$problems = [];
$checked = 0;

// --- 1. Router -> controller methods ---
$router = file_get_contents($root . '/public/index.php');
$assigns = [];
// $x = new App\Controllers\Foo();   (record offset so we resolve the nearest preceding assignment)
if (preg_match_all('/\$(\w+)\s*=\s*new\s+\\\\?(App\\\\[\w\\\\]+)\s*\(/', $router, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
    foreach ($m as $mm) { $assigns[$mm[1][0]][] = [$mm[0][1], $mm[2][0]]; }
}
$calls = [];
// (new App\Controllers\Foo())->bar()
if (preg_match_all('/new\s+\\\\?(App\\\\[\w\\\\]+)\s*\(\s*\)\s*\)\s*->\s*(\w+)\s*\(/', $router, $m, PREG_SET_ORDER)) {
    foreach ($m as $mm) { $calls[] = [$mm[1], $mm[2]]; }
}
// $x->bar()
if (preg_match_all('/\$(\w+)\s*->\s*(\w+)\s*\(/', $router, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
    foreach ($m as $mm) {
        $var = $mm[1][0];
        $pos = $mm[0][1];
        if (empty($assigns[$var])) continue;
        $cls = null;
        foreach ($assigns[$var] as [$aPos, $aCls]) {
            if ($aPos < $pos) { $cls = $aCls; } else { break; }
        }
        if ($cls) { $calls[] = [$cls, $mm[2][0]]; }
    }
}
foreach (array_unique(array_map(fn($c) => implode('::', $c), $calls)) as $sig) {
    [$cls, $method] = explode('::', $sig);
    $checked++;
    if (!class_exists($cls)) { $problems[] = "Router: class not found $cls (for ->$method)"; continue; }
    $rc = new ReflectionClass($cls);
    if (!$rc->hasMethod($method)) { $problems[] = "Router: missing method $cls::$method()"; continue; }
    if (!$rc->getMethod($method)->isPublic()) { $problems[] = "Router: non-public method $cls::$method()"; }
}

// --- 2. Controller view references ---
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app/Controllers'));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    $src = file_get_contents($f->getPathname());
    if (preg_match_all("#dirname\(__DIR__\)\s*\.\s*'(/Views/[^']+\.php)'#", $src, $m)) {
        foreach (array_unique($m[1]) as $v) {
            $checked++;
            if (!is_file($root . '/app' . $v)) {
                $problems[] = 'View missing: app' . $v . ' (referenced in ' . basename($f->getPathname()) . ')';
            }
        }
    }
}

echo "Checked $checked references.\n";
if ($problems) {
    echo count($problems) . " problem(s):\n - " . implode("\n - ", $problems) . "\n";
    exit(1);
}
echo "OK — no broken routes or view references.\n";
