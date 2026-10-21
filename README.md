# türkiye-vergi

Türkiye vergi hesaplamaları için bağımlılıksız PHP kütüphanesi. Gelir vergisi tarifesi, kurumlar vergisi, KDV, KDV tevkifatı, stopaj ve binek otomobil gider kısıtlamaları.

- **Kuruş hassasiyeti:** Tüm tutarlar tamsayı kuruş olarak taşınır, kayan nokta hatası olmaz.
- **Yıl bazlı resmî veri:** Her yılın oranları `data/{yıl}.json` dosyasında, kaynak bağlantısıyla birlikte durur. JSON olduğu için başka dillerden de okunabilir.
- **Tahmin yok:** Tarifesi tanımlanmamış bir yıl için önceki yılın tarifesi kullanılmaz, `UnknownYearException` fırlatılır. Yanlış vergiyle sessizce devam etmektense hata vermek daha güvenlidir.
- **Bağımlılık yok:** PHP 8.1 ve üzeri.

> Bu kütüphane hesaplama yardımcısıdır, mali müşavirlik hizmeti değildir. Beyanname öncesinde sonuçları yetkili bir meslek mensubuyla doğrulayın.

## Kurulum

```bash
composer require sevensdesign/turkiye-vergi
```

## Kullanım

```php
use SevensDesign\TurkiyeVergi\{Money, IncomeTax, CorporateTax, Vat, Withholding, PassengerCar, Tariff};

$matrah = Money::parse('500.000,00');          // 50000000 kuruş

IncomeTax::calculate($matrah, 2026);            // 9750000 → 97.500,00 TL
IncomeTax::marginalRate($matrah, 2026);         // 27
IncomeTax::breakdown($matrah, 2026);            // dilim dilim döküm

CorporateTax::calculate($matrah, 2026);         // %25 → 125.000,00 TL
CorporateTax::calculate($matrah, 2026, true);   // bankalar vb. %30

Vat::fromGross(Money::parse('1.200'), 20);      // ['net' => 100000, 'vat' => 20000, 'gross' => 120000]
Vat::fromNet(Money::parse('1.000'), 10);        // ['net' => 100000, 'vat' => 10000, 'gross' => 110000]
Vat::withholding(Money::parse('200'), 5);       // 5/10 tevkifat
Vat::period($hesaplanan, $indirilecek, $devreden); // ['payable' => ..., 'carry_forward' => ...]

Withholding::fromGross(Money::parse('10.000'), 2000); // %20 stopaj: brüt, kesinti, net
Withholding::fromNet(Money::parse('8.000'), 2000);    // netten brüte

PassengerCar::monthlyRentCap(2026);                         // 4600000 (46.000 TL, KDV hariç)
PassengerCar::rentRestriction($net, $kdv, 20, $kalanTavan); // tavanı aşan kısım KKEG
PassengerCar::expenseRestriction($net, $kdv);               // akaryakıt/bakımda %30 KKEG

Money::format(9750000);                         // "97.500,00"
Tariff::years();                                // [2025, 2026]
```

Oranlar tam yüzde (`20` = %20) olarak verilir. Stopaj gibi küsuratlı olabilen oranlar baz puan cinsindendir (`2000` = %20, `1750` = %17,5).

## Kapsam

| Konu | Dayanak | Durum |
|---|---|---|
| Gelir vergisi tarifesi (ücret dışı gelirler) | GVK md. 103 | 2025, 2026 |
| Kurumlar vergisi (genel ve özel oran) | KVK md. 32 | 2025, 2026 |
| KDV ayrıştırma, tevkifat, devreden KDV | KDVK md. 9, 29 | Var |
| Stopaj (brütten nete, netten brüte) | GVK md. 94 | Var |
| Binek otomobil kira tavanı ve %70 gider sınırı | GVK md. 40/5, KVK md. 11/1-b | 2025, 2026 |
| Ücret gelirleri tarifesi | GVK md. 103 | Planlanıyor |
| Asgari ücret istisnası, SGK primleri | | Planlanıyor |

## Yeni yıl tarifesi ekleme

Tarifeler her yıl genellikle Aralık sonunda Resmî Gazete'de yayımlanır.

1. `data/2026.json` dosyasını `data/{yeni yıl}.json` olarak kopyalayın.
2. Dilimleri, oranları ve tutarları resmî metne göre güncelleyin. `source` alanına resmî kaynağın bağlantısını yazın.
3. `tests/TaxTest.php` dosyasına yeni yılın dilim toplamlarını ekleyin ve `php tests/run.php` komutunu çalıştırın.

Her 15 Aralık'ta GitHub Actions, gelecek yılın dosyası yoksa uyarı verir.

## Katkı

Hata bildirimi ve pull request'ler memnuniyetle karşılanır. Lütfen her oran değişikliğinde resmî kaynak bağlantısı ekleyin.

## English

Dependency-free PHP library for Turkish tax calculations: progressive income tax brackets, corporate tax, VAT (KDV) split and withholding, withholding tax (stopaj) and passenger-car expense limits. Amounts are integer kuruş (1/100 TRY); yearly official rates live in `data/{year}.json` with source links. Unknown years throw instead of silently reusing an old tariff.

## Lisans

MIT
