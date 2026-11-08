<?php

namespace App\Support;

class NameFormatter
{
    /**
     * Merapikan penulisan nama orang.
     *
     * Contoh:
     *   "  bUdI;;   sAnToSo "  -> "Budi Santoso"
     *   "M .Abdul Aziz"        -> "M. Abdul Aziz"
     *   "M ABDUL AZIZ"         -> "M. Abdul Aziz"
     *   "dewi o'brien-smith"   -> "Dewi O'Brien-Smith"
     *
     * Aturan: simbol & angka dibuang, spasi dirapikan, kapital di awal
     * tiap kata, dan huruf tunggal dianggap inisial (diberi titik).
     * Fungsi ini idempoten: dipanggil berulang hasilnya tetap sama.
     */
    public static function normalize(?string $input): string
    {
        $s = $input ?? '';

        if (class_exists(\Normalizer::class)) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_C) ?: $s;
        }

        $s = preg_replace('/[^\p{L}\s.\'’-]/u', ' ', $s);   // buang angka & simbol: ; , ! @ dll
        $s = preg_replace('/\s+\./u', '.', $s);              // "M .Abdul" -> "M.Abdul"
        $s = preg_replace('/\.(?=\p{L})/u', '. ', $s);       // "M.Abdul"  -> "M. Abdul"
        $s = preg_replace('/\s+/u', ' ', $s);                // spasi ganda -> satu spasi
        $s = mb_strtolower(trim($s), 'UTF-8');

        // Kapital di awal tiap kata (juga setelah apostrof / tanda hubung)
        $s = preg_replace_callback(
            '/(^|[\s\'’-])(\p{L})/u',
            fn($m) => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
            $s
        );

        // Huruf tunggal -> inisial, beri titik
        return preg_replace('/(^|\s)(\p{L})(?=\s|$)/u', '$1$2.', $s);
    }
}
