<?php
declare(strict_types=1);

use SevensDesign\TurkiyeVergi\CorporateTax;
use SevensDesign\TurkiyeVergi\IncomeTax;
use SevensDesign\TurkiyeVergi\Money;
use SevensDesign\TurkiyeVergi\PassengerCar;
use SevensDesign\TurkiyeVergi\Tariff;
use SevensDesign\TurkiyeVergi\UnknownYearException;
use SevensDesign\TurkiyeVergi\Vat;
use SevensDesign\TurkiyeVergi\Withholding;

$tl = static fn (int $lira): int => Money::lira($lira);

// Tarife
eq([2025, 2026], array_slice(Tariff::years(), 0, 2), 'tanımlı yıllar');
throws(UnknownYearException::class, fn () => Tariff::for(1999), 'tanımsız yıl tahmin edilmez');
throws(InvalidArgumentException::class, fn () => Tariff::fromArray(['year' => 2030, 'income_tax' => ['brackets' => [
    ['up_to' => 100, 'rate' => 10], ['up_to' => 50, 'rate' => 20], ['up_to' => null, 'rate' => 30],
]], 'corporate_tax' => ['general_rate' => 25, 'special_rate' => 30]]), 'azalan dilim reddedilir');

// Gelir vergisi: GİB 2025 tarifesindeki dilim toplamları
eq($tl(23700), IncomeTax::calculate($tl(158000), 2025), '2025 1. dilim sonu');
eq($tl(58100), IncomeTax::calculate($tl(330000), 2025), '2025 2. dilim sonu');
eq($tl(185000), IncomeTax::calculate($tl(800000), 2025), '2025 3. dilim sonu');
eq($tl(1410000), IncomeTax::calculate($tl(4300000), 2025), '2025 4. dilim sonu');
eq($tl(1410000) + $tl(400000), IncomeTax::calculate($tl(5300000), 2025), '2025 son dilim');
eq(0, IncomeTax::calculate(0, 2025), 'sıfır matrah');

// 2026 tarifesi
eq($tl(28500), IncomeTax::calculate($tl(190000), 2026), '2026 1. dilim sonu');
eq($tl(70500), IncomeTax::calculate($tl(400000), 2026), '2026 2. dilim sonu');
eq($tl(232500), IncomeTax::calculate($tl(1000000), 2026), '2026 3. dilim sonu');
eq($tl(1737500), IncomeTax::calculate($tl(5300000), 2026), '2026 4. dilim sonu');
eq(27, IncomeTax::marginalRate($tl(500000), 2026), 'marjinal oran');
eq(3, count(IncomeTax::breakdown($tl(500000), 2026)), 'döküm satır sayısı');
throws(InvalidArgumentException::class, fn () => IncomeTax::calculate(-1, 2026), 'negatif matrah');

// Kurumlar vergisi
eq($tl(250000), CorporateTax::calculate($tl(1000000), 2026), 'genel oran %25');
eq($tl(300000), CorporateTax::calculate($tl(1000000), 2026, true), 'özel oran %30');
eq(0, CorporateTax::calculate(-$tl(5000), 2026), 'zararda vergi yok');

// KDV
eq(['net' => $tl(100), 'vat' => $tl(20), 'gross' => $tl(120)], Vat::fromGross($tl(120), 20), 'brütten ayırma');
eq(['net' => 83333, 'vat' => 16667, 'gross' => 100000], Vat::fromGross(100000, 20), 'kuruş yuvarlaması toplamı bozmaz');
eq(['net' => $tl(1000), 'vat' => $tl(100), 'gross' => $tl(1100)], Vat::fromNet($tl(1000), 10), 'nete ekleme');
eq(['withheld' => $tl(100), 'payable_by_seller' => $tl(100)], Vat::withholding($tl(200), 5), '5/10 tevkifat');
eq(['withheld' => 1401, 'payable_by_seller' => 600], Vat::withholding(2001, 7), '7/10 tevkifat yuvarlama');
eq(['payable' => $tl(300), 'carry_forward' => 0], Vat::period($tl(1000), $tl(500), $tl(200)), 'ödenecek KDV');
eq(['payable' => 0, 'carry_forward' => $tl(200)], Vat::period($tl(300), $tl(500)), 'devreden KDV');

// Stopaj
eq(['gross' => $tl(10000), 'withheld' => $tl(2000), 'net' => $tl(8000)], Withholding::fromGross($tl(10000), 2000), 'kira stopajı %20');
eq(['gross' => $tl(10000), 'withheld' => $tl(2000), 'net' => $tl(8000)], Withholding::fromNet($tl(8000), 2000), 'brütleştirme');

// Binek otomobil
eq($tl(46000), PassengerCar::monthlyRentCap(2026), '2026 kira tavanı');
eq($tl(37000), PassengerCar::monthlyRentCap(2025), '2025 kira tavanı');
eq(['kkeg' => $tl(4000), 'kkeg_vat' => $tl(800)], PassengerCar::rentRestriction($tl(50000), $tl(10000), 20, $tl(46000)), 'kira tavanı aşımı');
eq(['kkeg' => 0, 'kkeg_vat' => 0], PassengerCar::rentRestriction($tl(40000), $tl(8000), 20, $tl(46000)), 'tavan altında KKEG yok');
eq(['kkeg' => $tl(300), 'kkeg_vat' => $tl(60)], PassengerCar::expenseRestriction($tl(1000), $tl(200)), 'akaryakıt %30 KKEG');
eq($tl(97500), IncomeTax::calculate($tl(500000), 2026), 'README örneği');
