<?php
namespace app\helpers;

class FormatHelper
{
    /**
     * Format angka menjadi Rupiah dengan simbol "Rp".
     *
     * Usage:
     * ```php
     * echo FormatHelper::Rupiah(2500000);            // Rp 2.500.000
     * echo FormatHelper::Rupiah(null);               // Rp 0
     * echo FormatHelper::Rupiah(1500000, 'IDR');     // IDR 1.500.000
     * ```
     *
     * @param float|null $value Nilai yang akan diformat
     * @param string $symbol Simbol mata uang, default "Rp"
     * @return string Hasil format Rupiah
     */
    public static function Rupiah(?float $value, string $symbol = 'Rp'): string
    {
        $value = $value ?? 0;
        return $symbol . ' ' . number_format($value, 0, ',', '.');
    }

    /**
     * Format angka menjadi mata uang dengan desimal dan simbol fleksibel.
     *
     * Usage:
     * ```php
     * echo FormatHelper::Currency(1234.56);           // Rp 1.234,56
     * echo FormatHelper::Currency(null, '$', 0);         // $ 0
     * echo FormatHelper::Currency(987654.32, 'IDR', 2);  // IDR 987.654,32
     * ```
     *
     * @param float|null $value Nilai yang akan diformat
     * @param string|null $symbol Simbol mata uang
     * @param int $decimals Jumlah desimal
     * @return string Hasil format mata uang
     */
    public static function Currency(?float $value, ?string $symbol = null, int $decimals = 2): string
    {
        $value = $value ?? 0;
        $symbol = $symbol ?? '';
        $space = $symbol !== '' ? ' ' : '';
        return $symbol . $space . number_format($value, $decimals, '.', ',');
    }

    /**
     * Format angka desimal.
     *
     * Usage:
     * ```php
     * echo FormatHelper::Decimal(2345.678);       // 2.345,68
     * echo FormatHelper::Decimal(1234.5, 3);      // 1.234,500
     * ```
     *
     * @param float $value Nilai desimal
     * @param int $decimals Jumlah desimal
     * @return string Hasil format desimal
     */
    public static function Decimal($value, int $decimals = 2): string
    {
        // Ubah nilai null, kosong, atau non-numeric jadi 0
        if ($value === null || $value === '' || !is_numeric($value)) {
            $value = 0;
        }

        return number_format((float)$value, $decimals, '.', '');
    }

    public static function decimalFields(array $data, array $decimalKeys): array
    {
        $applyDecimal = function ($item) use ($decimalKeys) {
            foreach ($decimalKeys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = self::Decimal($item[$key]);
                }
            }
            return $item;
        };

        return (!empty($data) && array_is_list($data))
            ? array_map($applyDecimal, $data)
            : $applyDecimal($data);
    }

    public static function decimalFieldClosures(array $data, array $decimalKeys): array
    {
        // Helper closure untuk satu item
        $applyClosure = function ($item) use ($decimalKeys) {
            foreach ($decimalKeys as $key) {
                $item[$key] = fn($model) => self::Decimal($model->$key);
            }
            return $item;
        };

        // Deteksi apakah $data adalah array numerik (multi row)
        if (!empty($data) && array_is_list($data)) {
            return array_map($applyClosure, $data);
        }

        // Single array
        return $applyClosure($data);
    }


    public static function integerFields(array $fields, array $integerKeys): array
    {
        // Helper closure untuk proses satu item
        $applyInteger = function ($item) use ($integerKeys) {
            foreach ($integerKeys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = (int) $item[$key];
                }
            }
            return $item;
        };

        // Jika array multidimensi (numeric index di level pertama)
        if (!empty($fields) && array_is_list($fields)) {
            return array_map($applyInteger, $fields);
        }

        // Jika array tunggal
        return $applyInteger($fields);
    }

    /**
     * Menghapus key tertentu dari array, baik single maupun multi array.
     *
     * @param array $data  Data yang ingin dibersihkan (single atau multi array)
     * @param array $unsetKeys  Daftar key yang akan dihapus
     * @return array
     */
    public static function unsetArrayKeys(array $data, array $unsetKeys): array
    {
        // Helper closure untuk satu item
        $applyUnset = function (array $item) use ($unsetKeys): array {
            return array_diff_key($item, array_flip($unsetKeys));
        };

        // Jika array of arrays (numeric index di level pertama)
        if (!empty($data) && array_is_list($data)) {
            return array_map($applyUnset, $data);
        }

        // Jika single associative array
        return $applyUnset($data);
    }

    /**
     * Format angka menjadi persen.
     *
     * Usage:
     * ```php
     * echo FormatHelper::Persen(12.3456);       // 12,35%
     * echo FormatHelper::Persen(0.5, 1);        // 0,5%
     * ```
     *
     * @param float $value Nilai persen
     * @param int $decimals Jumlah desimal
     * @return string Hasil format persen
     */
    public static function Persen($value, $decimals = 2)
    {
        $value = $value ?? 0;
        return number_format($value, $decimals, '.', ',') . '%';
    }

    /**
     * Format nomor telepon Indonesia dengan pemisah fleksibel.
     *
     * Usage:
     * ```php
     * echo FormatHelper::Phone('08123456789');          // 628123456789
     * echo FormatHelper::Phone('08123456789', ' ');     // 62812 3456 789
     * echo FormatHelper::Phone('08123456789', '');     // 628123456789
     * echo FormatHelper::Phone('08123456789', '-');     // 62812-3456-789
     * echo FormatHelper::Phone('08123456789', null);    // 628123456789
     * echo FormatHelper::Phone('628123456789', null);    // 628123456789
     * ```
     *
     * @param string $value Nomor telepon
     * @param string|null $separator Karakter pemisah, default "-"
     * @return string Nomor telepon terformat
     */
    public static function Phone($value, $separator = null)
    {
        if ($separator === null || $separator === '') {
            $separator = '';
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (substr($digits, 0, 1) === '0') {
            $digits = '62' . substr($digits, 1);
        }

        if (preg_match('/^(\d{5})(\d{4})(\d+)/', $digits, $matches)) {
            return $matches[1] . $separator . $matches[2] . $separator . $matches[3];
        }

        return $digits;
    }

    /**
     * Format waktu menjadi jam:menit.
     *
     * Usage:
     * ```php
     * echo FormatHelper::TimeHM('2025-10-30 15:30:00');               // 15:30
     * echo FormatHelper::TimeHM(null);                                // 00:00
     * echo FormatHelper::TimeHM('2025-10-30 15:30:00', 'Asia/Jakarta'); // 22:30
     * ```
     *
     * @param string|null $value Waktu dalam format string
     * @param string|null $tz Timezone output, optional
     * @return string Hasil format jam:menit
     */
    public static function TimeHM(?string $value, ?string $tz = null): string
    {
        if (!$value) return '00:00';
        $dt = new \DateTime($value, new \DateTimeZone('UTC'));
        if ($tz) $dt->setTimezone(new \DateTimeZone($tz));
        return $dt->format('H:i');
    }

    /**
     * Format waktu menjadi jam:menit:detik.
     *
     * Usage:
     * ```php
     * echo FormatHelper::TimeHMS('2025-10-30 15:30:00');               // 15:30:00
     * echo FormatHelper::TimeHMS(null);                                 // 00:00:00
     * echo FormatHelper::TimeHMS('2025-10-30 15:30:00', 'Asia/Jakarta'); // 22:30:00
     * ```
     *
     * @param string|null $value Waktu dalam format string
     * @param string|null $tz Timezone output, optional
     * @return string Hasil format jam:menit:detik
     */
    public static function TimeHMS(?string $value, ?string $tz = null): string
    {
        if (!$value) return '00:00:00';
        $dt = new \DateTime($value, new \DateTimeZone('UTC'));
        if ($tz) $dt->setTimezone(new \DateTimeZone($tz));
        return $dt->format('H:i:s');
    }

    /**
     * Format ukuran file dari bytes ke KB, MB, GB, TB.
     *
     * Usage:
     * ```php
     * echo FormatHelper::FileSize(1048576); // 1 MB
     * ```
     *
     * @param float $bytes Ukuran file dalam byte
     * @param int $decimals Jumlah desimal
     * @return string Ukuran file terformat
     */
    public static function FileSize($bytes, $decimals = 2)
    {
        $sizes = ['B','KB','MB','GB','TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($sizes)-1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, $decimals) . ' ' . $sizes[$i];
    }

    /**
     * Format angka besar menjadi K, M, B.
     *
     * Usage:
     * ```php
     * echo FormatHelper::ShortNumber(1500);       // 1.5K
     * echo FormatHelper::ShortNumber(2500000);    // 2.5M
     * echo FormatHelper::ShortNumber(3500000000); // 3.5B
     * ```
     *
     * @param float $value Angka
     * @return string Hasil format
     */
    public static function ShortNumber($value)
    {
        return match (true) {
            $value >= 1000000000 => round($value / 1000000000, 2) . 'B',
            $value >= 1000000    => round($value / 1000000, 2) . 'M',
            $value >= 1000       => round($value / 1000, 2) . 'K',
            default              => (string)$value,
        };
    }

    /**
     * Memotong string panjang dengan suffix.
     *
     * Usage:
     * ```php
     * echo FormatHelper::Truncate('Lorem ipsum dolor sit amet', 10); // Lorem ipsu...
     * ```
     *
     * @param string|null $text Teks
     * @param int $length Panjang maksimal
     * @param string $suffix Suffix jika dipotong
     * @return string Teks terpotong
     */
    public static function Truncate(?string $text, int $length = 50, string $suffix = '...'): string
    {
        if (!$text) return '';
        return mb_strlen($text) <= $length ? $text : mb_substr($text, 0, $length) . $suffix;
    }

    public static function Weight(?float $value, string $unit = 'kg', int $decimals = 2): string
    {
        $value = $value ?? 0; // nullsafe
        return number_format($value, $decimals, '.', ',') . ' ' . $unit;
    }
}