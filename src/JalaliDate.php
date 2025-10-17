<?php
namespace HonarContract;

/**
 * Jalali (Persian/Shamsi) Date Converter
 * Converts Gregorian dates to Persian calendar
 */
class JalaliDate {
    
    /**
     * Convert Gregorian date to Jalali
     * @param string $gregorianDate Date in Y-m-d format
     * @return string Jalali date in Persian format
     */
    public static function toJalali($gregorianDate) {
        if (empty($gregorianDate)) {
            return '';
        }
        
        $parts = explode('-', $gregorianDate);
        if (count($parts) !== 3) {
            return $gregorianDate;
        }
        
        $gy = intval($parts[0]);
        $gm = intval($parts[1]);
        $gd = intval($parts[2]);
        
        $jalali = self::gregorianToJalali($gy, $gm, $gd);
        
        $persianMonths = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        ];
        
        $persianNumerals = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        
        $day = str_replace(range(0, 9), $persianNumerals, $jalali[2]);
        $month = $persianMonths[$jalali[1]];
        $year = str_replace(range(0, 9), $persianNumerals, $jalali[0]);
        
        return $day . ' ' . $month . ' ' . $year;
    }
    
    /**
     * Core conversion algorithm
     */
    private static function gregorianToJalali($gy, $gm, $gd) {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        
        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }
        
        if ($gm > 2) {
            $gy2 = $gy + 1;
        } else {
            $gy2 = $gy;
        }
        
        $days = (365 * $gy) + (intval(($gy2 + 3) / 4)) - (intval(($gy2 + 99) / 100)) + (intval(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
        $jy += 33 * intval($days / 12053);
        $days %= 12053;
        $jy += 4 * intval($days / 1461);
        $days %= 1461;
        
        if ($days > 365) {
            $jy += intval(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        
        if ($days < 186) {
            $jm = 1 + intval($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intval(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        
        return [$jy, $jm, $jd];
    }
    
    /**
     * Get Persian numerals for a number
     */
    public static function toPersianNumerals($number) {
        $persianNumerals = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str_replace(range(0, 9), $persianNumerals, $number);
    }
}
