<?php
declare(strict_types=1);

namespace SevensDesign\TurkiyeVergi;

/** Gelir vergisi tarifesinde bir dilim. $upTo kuruş; son dilimde null (üst sınır yok). */
final class Bracket
{
    public function __construct(
        public readonly ?int $upTo,
        public readonly int $rate,
    ) {
    }
}
