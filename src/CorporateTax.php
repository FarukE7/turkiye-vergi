<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

/** Kurumlar vergisi (KVK md. 32). */
final class CorporateTax
{
    /**
     * @param bool $special Kanunda sayılan kurumlar (bankalar, finansal kiralama, sigorta vb.) için yüksek oran.
     */
    public static function calculate(int $base, int|Tariff $year, bool $special = false): int
    {
        return Money::percent(max(0, $base), self::rate($year, $special) * 100);
    }

    public static function rate(int|Tariff $year, bool $special = false): int
    {
        $tariff = $year instanceof Tariff ? $year : Tariff::for($year);
        return $special ? $tariff->corporateSpecialRate : $tariff->corporateRate;
    }
}
