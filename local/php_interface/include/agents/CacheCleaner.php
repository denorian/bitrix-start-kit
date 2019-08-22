<?php

/**
 * Class CacheCleaner
 * В Битриксе бывают кейсы что инвалидированный кеш не удаляется и накапливается на сервере
 * для включения в агентах битрикса добавть CacheCleaner::cleanExpireCache();
 */
class CacheCleaner
{
    public static function cleanExpireCache($path = "")
    {
        if (!class_exists("CFileCacheCleaner")) {
            require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/cache_files_cleaner.php");
        }

        $curentTime = mktime();

        if (defined("BX_CRONTAB") && BX_CRONTAB === true)
            $endTime = time() + 15; //Если на кроне, то работаем 15 секунд
        else
            $endTime = time() + 1; //Если на хитах, то не более секунды
        $obCacheCleaner = new CFileCacheCleaner("all");

        if (!$obCacheCleaner->InitPath($path)) {
            //Произошла ошибка
            return "CacheCleaner::cleanExpireCache();";
        }

        $obCacheCleaner->Start();

        while ($file = $obCacheCleaner->GetNextFile()) {
            if (is_string($file)) {
                $date_expire = $obCacheCleaner->GetFileExpiration($file);
                if ($date_expire) {
                    if ($date_expire < $curentTime) {
                        unlink($file);
                    }
                }
                if (time() >= $endTime)
                    break;
            }
        }

        if (is_string($file)) {
            return "CacheCleaner::cleanExpireCache(\"" . $file . "\");";
        } else {
            return "CacheCleaner::cleanExpireCache();";
        }
    }
}