<?php
namespace now;

/**
 * xxtea的加解密，目前用来进行session的加解密
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class xxtea {

    /**
     * 进行加密
     * @author 欧远宁
     * @param string $str 需要加密的字符串
     * @param string $key 密钥
     * @return string
     */
    public static function encrypt($str, $key) {
        if ($str == '') {
            return '';
        }
        $v = self::str2long($str, TRUE);
        $k = self::str2long($key, FALSE);
        $n = count($v) - 1;

        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = 0;
        while (0 < $q--) {
            $sum = self::int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p++) {
                $y = $v[$p + 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $z = $v[$p] = self::int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$n] = self::int32($v[$n] + $mx);
        }
        return self::long2str($v, FALSE);
    }

    /**
     * 进行解密
     * @author 欧远宁
     * @param string $str 需要解密的字符串
     * @param string $key 密钥
     * @return string
     */
    public static function decrypt($str, $key) {
        if ($str == "") {
            return "";
        }
        $v = self::str2long($str, FALSE);
        $k = self::str2long($key, FALSE);
        $n = count($v) - 1;

        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = self::int32($q * $delta);
        while ($sum != 0) {
            $e = $sum >> 2 & 3;
            for ($p = $n; $p > 0; $p--) {
                $z = $v[$p - 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $y = $v[$p] = self::int32($v[$p] - $mx);
            }
            $z = $v[$n];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[0] = self::int32($v[0] - $mx);
            $sum = self::int32($sum - $delta);
        }
        return self::long2str($v, TRUE);
    }

    /**
     * 
     * @author 欧远宁
     * @param string $v
     * @param boolean $w
     * @return string
     */
    private static function long2str($v, $w) {
        $len = count($v);
        $s = array();
        for ($i = 0; $i < $len; $i++) {
            $s[$i] = pack("V", $v[$i]);
        }
        if ($w) {
            return substr(join('', $s), 0, $v[$len - 1]);
        }else{
            return join('', $s);
        }
    }
    
    /**
     *
     * @author 欧远宁
     * @param string $v
     * @param boolean $w
     * @return string
     */
    private static function str2long($s, $w) {
        $v = unpack("V*", $s. str_repeat("\0", (4 - strlen($s) % 4) & 3));
        $v = array_values($v);
        if ($w) {
            $v[count($v)] = strlen($s);
        }
        return $v;
    }
    
    /**
     *
     * @author 欧远宁
     * @param string $n
     * @return int
     */
    private static function int32($n) {
        while ($n >= 2147483648) $n -= 4294967296;
        while ($n <= -2147483649) $n += 4294967296;
        return (int)$n;
    }
}