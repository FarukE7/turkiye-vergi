<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

use InvalidArgumentException;

/**
 * Tüm tutarlar tamsayı kuruş olarak taşınır; kayan nokta yuvarlama hatası olmaz.
 */
final class Money
{
    /**
     * "1.234,56", "1234,56", "1234.56", "10.000", "₺1.234,56 TL", 1234 veya 1234.5 → kuruş.
     * Yalnız nokta içeren "1.234" gibi üç haneli gruplar Türkçe binlik ayıracı sayılır (1234 TL).
     */
    public static function parse(string|int|float $input): int
    {
        $v = trim(str_replace(['₺', 'TL', ' ', "\u{00A0}"], '', (string) $input));
        $negative = str_starts_with($v, '-');
        if ($negative) {
            $v = substr($v, 1);
        }
        if (str_contains($v, ',')) {
            $v = str_replace(',', '.', str_replace('.', '', $v));
        } elseif (preg_match('/^[1-9]\d{0,2}(?:\.\d{3})+$/', $v)) {
            $v = str_replace('.', '', $v);
        }
        if (!preg_match('/^\d{1,15}(?:\.\d{1,2})?$/', $v)) {
            throw new InvalidArgumentException('Tutar 1.234,56 biçiminde olmalı (en fazla iki ondalık basamak).');
        }
        [$whole, $fraction] = array_pad(explode('.', $v), 2, '');
        $kurus = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        return $negative ? -$kurus : $kurus;
    }

    /** Tam TL → kuruş. */
    public static function lira(int $lira): int
    {
        return $lira * 100;
    }

    /** Kuruş → "1.234,56" */
    public static function format(int $kurus): string
    {
        return ($kurus < 0 ? '-' : '') . number_format(intdiv(abs($kurus), 100), 0, ',', '.')
            . ',' . str_pad((string) (abs($kurus) % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Kuruş → "1234.56" (veritabanı ve JSON için). */
    public static function toDecimal(int $kurus): string
    {
        return ($kurus < 0 ? '-' : '') . intdiv(abs($kurus), 100) . '.'
            . str_pad((string) (abs($kurus) % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Tutarın yüzdesi; oran baz puan (1/10000) cinsinden. Sonuç kuruşa, yarım sıfırdan uzağa yuvarlanır.
     * Örn. %20 = 2000, %0,75 = 75.
     */
    public static function percent(int $kurus, int $basisPoints): int
    {
        return ($kurus < 0 ? -1 : 1) * intdiv(abs($kurus) * $basisPoints + 5000, 10000);
    }
}
