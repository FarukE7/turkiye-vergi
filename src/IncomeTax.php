<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

use InvalidArgumentException;

/** Gelir vergisi (GVK md. 103, artan oranlı tarife). */
final class IncomeTax
{
    /** Matraha (kuruş) isabet eden gelir vergisi (kuruş). */
    public static function calculate(int $base, int|Tariff $year): int
    {
        return array_sum(array_column(self::breakdown($base, $year), 'tax'));
    }

    /**
     * Dilim dilim hesap dökümü.
     *
     * @return list<array{from:int, to:?int, rate:int, taxable:int, tax:int}>
     */
    public static function breakdown(int $base, int|Tariff $year): array
    {
        if ($base < 0) {
            throw new InvalidArgumentException('Matrah negatif olamaz.');
        }
        $tariff = $year instanceof Tariff ? $year : Tariff::for($year);
        $rows = [];
        $from = 0;
        foreach ($tariff->brackets as $bracket) {
            $taxable = max(0, ($bracket->upTo === null ? $base : min($base, $bracket->upTo)) - $from);
            $rows[] = ['from' => $from, 'to' => $bracket->upTo, 'rate' => $bracket->rate, 'taxable' => $taxable,
                'tax' => Money::percent($taxable, $bracket->rate * 100)];
            if ($bracket->upTo === null || $base <= $bracket->upTo) {
                break;
            }
            $from = $bracket->upTo;
        }
        return $rows;
    }

    /** Matrahın girdiği en yüksek dilimin oranı (marjinal oran, %). */
    public static function marginalRate(int $base, int|Tariff $year): int
    {
        $rows = self::breakdown($base, $year);
        return end($rows)['rate'];
    }
}
