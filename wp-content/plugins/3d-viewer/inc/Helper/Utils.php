<?php
namespace BP3D\Helper;

class Utils {

    public static function isset($array, $key, $default = false){
        if(isset($array[$key])){
            return $array[$key];
        }
        return $default;
    }

    public static function isset2($array, $key1, $key2, $default = false){
        if(isset($array[$key1][$key2])){
            return $array[$key1][$key2];
        }
        return $default;
    }

     /**
     * convert hex to rgb color
     */
    public static function hexToRGB($hex, $alpha = false){
        $hex      = str_replace('#', '', $hex);
        $length   = strlen($hex);
        $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
        $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
        $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
        if ( $alpha ) {
            $rgb['a'] = $alpha;
        }
        return $rgb;
    }
}