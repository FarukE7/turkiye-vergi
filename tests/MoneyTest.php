<?php
declare(strict_types=1);

use SevensDesign\TurkiyeVergi\Money;

eq(123456, Money::parse('1.234,56'), 'Türkçe biçim');
eq(123456, Money::parse('1234,56'), 'virgüllü ondalık');
eq(123456, Money::parse('1234.56'), 'noktalı ondalık');
eq(1000000, Money::parse('10.000'), 'Türkçe binlik');
eq(123456700, Money::parse('1.234.567'), 'çoklu binlik');
eq(123450, Money::parse('₺1.234,5 TL'), 'para birimi işaretleri');
eq(-5050, Money::parse('-50,50'), 'negatif');
eq(1234, Money::parse(12.34), 'float girdi');
eq(500, Money::parse(5), 'int girdi');
throws(InvalidArgumentException::class, fn () => Money::parse('12,345'), 'üç ondalık reddedilir');
throws(InvalidArgumentException::class, fn () => Money::parse('abc'), 'metin reddedilir');

eq('1.234.567,89', Money::format(123456789), 'biçimlendirme');
eq('-0,05', Money::format(-5), 'negatif küçük tutar');
eq('1234.56', Money::toDecimal(123456), 'ondalık metin');

eq(2000, Money::percent(10000, 2000), '%20');
eq(1, Money::percent(5, 1000), 'yarım kuruş yukarı yuvarlanır');
eq(-1, Money::percent(-5, 1000), 'negatifte simetrik yuvarlama');
