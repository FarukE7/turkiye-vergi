<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

use InvalidArgumentException;

/** KDV hesapları. Tutarlar kuruş, oranlar tam yüzde (%1, %10, %20). */
final class Vat
{
    /**
     * KDV dahil tutarı matrah ve KDV'ye ayırır. Matrah kuruşa yuvarlanır, KDV farktan bulunur;
     * böylece matrah + KDV her zaman brüte eşittir.
     *
     * @return array{net:int, vat:int, gross:int}
     */
    public static function fromGross(int $gross, int $rate): array
    {
        self::assertRate($rate);
        $net = ($gross < 0 ? -1 : 1) * intdiv(abs($gross) * 100 + intdiv(100 + $rate, 2), 100 + $rate);
        return ['net' => $net, 'vat' => $gross - $net, 'gross' => $gross];
    }

    /** @return array{net:int, vat:int, gross:int} */
    public static function fromNet(int $net, int $rate): array
    {
        self::assertRate($rate);
        $vat = Money::percent($net, $rate * 100);
        return ['net' => $net, 'vat' => $vat, 'gross' => $net + $vat];
    }

    /**
     * KDV tevkifatı (KDV-2). Örn. 5/10 tevkifat: withholding($kdv, 5).
     *
     * @return array{withheld:int, payable_by_seller:int}
     */
    public static function withholding(int $vat, int $numerator, int $denominator = 10): array
    {
        if ($denominator <= 0 || $numerator < 0 || $numerator > $denominator) {
            throw new InvalidArgumentException('Tevkifat oranı 0/10 ile 10/10 arasında olmalı.');
        }
        $withheld = ($vat < 0 ? -1 : 1) * intdiv(abs($vat) * $numerator * 2 + $denominator, $denominator * 2);
        return ['withheld' => $withheld, 'payable_by_seller' => $vat - $withheld];
    }

    /**
     * Dönem sonu: hesaplanan KDV − indirilecek KDV − devreden KDV.
     * Devreden KDV takvim yılı sonunda sıfırlanmaz (KDVK md. 29/2).
     *
     * @return array{payable:int, carry_forward:int}
     */
    public static function period(int $outputVat, int $inputVat, int $carryIn = 0): array
    {
        $diff = $outputVat - $inputVat - $carryIn;
        return ['payable' => max(0, $diff), 'carry_forward' => max(0, -$diff)];
    }

    private static function assertRate(int $rate): void
    {
        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException('KDV oranı 0 ile 100 arasında olmalı.');
        }
    }
}
