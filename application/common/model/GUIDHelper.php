<?php

namespace app\common\model;


class GUIDHelper
{
    public static function getGUID()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12)
                . chr(125);// "}"
            return $uuid;
        }
    }

    //返回32位字符串
    public static function getGUID32()
    {
        return preg_replace('/[-{}]/', '', self::getGUID());
    }

    //返回两个UUID组成的64位字符串
    public static function getGUID64()
    {
        return preg_replace('/[-{}]/', '', self::getGUID() . self::getGUID());
    }
}