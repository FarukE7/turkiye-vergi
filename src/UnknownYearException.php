<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

use RuntimeException;

/** İstenen yılın resmî tarifesi henüz tanımlı değil. Önceki yılın tarifesi bilerek yeniden kullanılmaz. */
final class UnknownYearException extends RuntimeException
{
    public function __construct(public readonly int $year)
    {
        parent::__construct(sprintf('%d yılı vergi tarifesi tanımlı değil. Tanımlı yıllar: %s.', $year, implode(', ', Tariff::years())));
    }
}
