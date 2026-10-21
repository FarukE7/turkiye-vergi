<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

use InvalidArgumentException;

/** Gelir/kurumlar vergisi stopajı (tevkifat): brüt tutar üzerinden sabit oran. */
final class Withholding
{
    /**
     * @param int $basisPoints Oran baz puan cinsinden (%20 = 2000, %17,5 = 1750).
     * @return array{gross:int, withheld:int, net:int}
     */
    public static function fromGross(int $gross, int $basisPoints): array
    {
        self::assertRate($basisPoints);
        $withheld = Money::percent($gross, $basisPoints);
        return ['gross' => $gross, 'withheld' => $withheld, 'net' => $gross - $withheld];
    }

    /**
     * Net ödenecek tutardan brütü bulur (brütleştirme).
     *
     * @return array{gross:int, withheld:int, net:int}
     */
    public static function fromNet(int $net, int $basisPoints): array
    {
        self::assertRate($basisPoints);
        if ($basisPoints === 10000) {
            throw new InvalidArgumentException('%100 stopajda net tutardan brüt bulunamaz.');
        }
        $gross = ($net < 0 ? -1 : 1) * intdiv(abs($net) * 10000 * 2 + (10000 - $basisPoints), (10000 - $basisPoints) * 2);
        return ['gross' => $gross, 'withheld' => $gross - $net, 'net' => $net];
    }

    private static function assertRate(int $basisPoints): void
    {
        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw new InvalidArgumentException('Stopaj oranı 0 ile 10000 baz puan arasında olmalı.');
        }
    }
}
