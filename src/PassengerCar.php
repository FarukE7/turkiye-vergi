<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

/** Binek otomobil gider kısıtlamaları (GVK md. 40/5, KVK md. 11/1-b). */
final class PassengerCar
{
    /** İşletme giderlerinin (akaryakıt, bakım vb.) gider yazılabilen oranı (%). */
    public const DEDUCTIBLE_EXPENSE_RATE = 70;

    /** Yılın aylık kira tavanı (kuruş, KDV hariç); tanımsızsa null. */
    public static function monthlyRentCap(int|Tariff $year): ?int
    {
        return ($year instanceof Tariff ? $year : Tariff::for($year))->monthlyRentCap;
    }

    /**
     * Akaryakıt, bakım gibi giderlerde %70 sınırı: kalan %30 kanunen kabul edilmeyen gider (KKEG).
     *
     * @return array{kkeg:int, kkeg_vat:int}
     */
    public static function expenseRestriction(int $net, int $vat): array
    {
        $bp = (100 - self::DEDUCTIBLE_EXPENSE_RATE) * 100;
        return ['kkeg' => Money::percent($net, $bp), 'kkeg_vat' => Money::percent($vat, $bp)];
    }

    /**
     * Kira bedelinde aylık tavan. $remainingCap, o ay için tavandan kalan tutardır (aynı ay birden çok
     * fatura varsa önceki faturalar düşülmüş olarak verilmelidir). Tavanı aşan kısım KKEG olur;
     * aşan kısma isabet eden KDV (en çok belgedeki KDV) indirilemez.
     *
     * @return array{kkeg:int, kkeg_vat:int}
     */
    public static function rentRestriction(int $net, int $vat, int $vatRate, int $remainingCap): array
    {
        $kkeg = max(0, $net - max(0, $remainingCap));
        return ['kkeg' => $kkeg, 'kkeg_vat' => min($vat, Money::percent($kkeg, $vatRate * 100))];
    }
}
