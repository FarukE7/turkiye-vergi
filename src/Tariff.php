<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

use InvalidArgumentException;

/**
 * Bir yılın resmî oranları ve tutarları. Veriler data/{yıl}.json dosyalarından okunur.
 * Tanımsız yıl için tarife tahmin edilmez; UnknownYearException fırlatılır.
 */
final class Tariff
{
    /** @var array<int, self> */
    private static array $cache = [];

    /**
     * @param list<Bracket> $brackets
     * @param array<string, string> $sources
     */
    private function __construct(
        public readonly int $year,
        public readonly string $reviewed,
        public readonly array $brackets,
        public readonly int $corporateRate,
        public readonly int $corporateSpecialRate,
        public readonly ?int $monthlyRentCap,
        public readonly array $sources,
    ) {
    }

    public static function for(int $year): self
    {
        if (isset(self::$cache[$year])) {
            return self::$cache[$year];
        }
        $file = self::dataDir() . '/' . $year . '.json';
        if (!is_file($file)) {
            throw new UnknownYearException($year);
        }
        $data = json_decode((string) file_get_contents($file), true, 16, JSON_THROW_ON_ERROR);
        return self::$cache[$year] = self::fromArray($data);
    }

    /** @return list<int> Tanımlı yıllar, küçükten büyüğe. */
    public static function years(): array
    {
        $years = array_map(static fn (string $f): int => (int) basename($f, '.json'), glob(self::dataDir() . '/[0-9][0-9][0-9][0-9].json') ?: []);
        sort($years);
        return $years;
    }

    public static function latestYear(): int
    {
        $years = self::years();
        return end($years);
    }

    public static function has(int $year): bool
    {
        return is_file(self::dataDir() . '/' . $year . '.json');
    }

    /** @param array<string, mixed> $d */
    public static function fromArray(array $d): self
    {
        $brackets = [];
        $previous = 0;
        $rows = $d['income_tax']['brackets'] ?? [];
        foreach ($rows as $i => $row) {
            $last = $i === array_key_last($rows);
            $upTo = $row['up_to'] === null ? null : Money::lira((int) $row['up_to']);
            $rate = $row['rate'];
            if (!is_int($rate) || $rate < 0 || $rate > 100) {
                throw new InvalidArgumentException("{$d['year']} tarifesinde geçersiz oran.");
            }
            if ($last !== ($upTo === null) || ($upTo !== null && $upTo <= $previous)) {
                throw new InvalidArgumentException("{$d['year']} tarifesinde dilim sınırları artan olmalı ve yalnız son dilim sınırsız olmalı.");
            }
            $brackets[] = new Bracket($upTo, $rate);
            $previous = (int) $upTo;
        }
        if (!$brackets) {
            throw new InvalidArgumentException("{$d['year']} tarifesinde gelir vergisi dilimi yok.");
        }
        $cap = $d['passenger_car']['monthly_rent_cap'] ?? null;
        return new self(
            (int) $d['year'],
            (string) ($d['reviewed'] ?? ''),
            $brackets,
            (int) $d['corporate_tax']['general_rate'],
            (int) $d['corporate_tax']['special_rate'],
            $cap === null ? null : Money::lira((int) $cap),
            array_filter([
                'income_tax' => $d['income_tax']['source'] ?? null,
                'corporate_tax' => $d['corporate_tax']['source'] ?? null,
            ]),
        );
    }

    private static function dataDir(): string
    {
        return dirname(__DIR__) . '/data';
    }
}
