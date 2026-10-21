<?php
declare(strict_types=1);

// Bağımlılıksız test çalıştırıcı: php tests/run.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'SevensDesign\\TurkiyeVergi\\';
    if (str_starts_with($class, $prefix)) {
        require dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

$failures = 0;
$count = 0;

function eq(mixed $expected, mixed $actual, string $name): void
{
    global $failures, $count;
    $count++;
    if ($expected !== $actual) {
        $failures++;
        fwrite(STDERR, "BAŞARISIZ: $name\n  beklenen: " . var_export($expected, true) . "\n  gelen:    " . var_export($actual, true) . "\n");
    }
}

function throws(string $class, callable $fn, string $name): void
{
    global $failures, $count;
    $count++;
    try {
        $fn();
        $failures++;
        fwrite(STDERR, "BAŞARISIZ: $name ($class bekleniyordu, hata yok)\n");
    } catch (Throwable $e) {
        if (!$e instanceof $class) {
            $failures++;
            fwrite(STDERR, "BAŞARISIZ: $name ($class bekleniyordu, " . $e::class . " geldi)\n");
        }
    }
}

foreach (glob(__DIR__ . '/*Test.php') as $file) {
    require $file;
}

echo "$count doğrulama, $failures hata\n";
exit($failures ? 1 : 0);
