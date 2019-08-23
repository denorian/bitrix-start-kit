<?php

use Bitrix\Main\Data\Cache;

/**
 * Class CacheCleaner
 * В Битриксе бывают кейсы что инвалидированный кеш не удаляется и накапливается на сервере
 * для включения в агентах битрикса добавть CacheCleaner::cleanExpireCache();
 */
class CacheCleaner
{
    public static function cleanExpireCache($path = "")
    {
        if (Cache::getCacheEngineType() == "cacheenginefiles") {

            if (!class_exists("CFileCacheCleaner")) {
                require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/cache_files_cleaner.php");
            }
            set_time_limit(0);

            $curentTime = mktime();

            if (defined("BX_CRONTAB") && BX_CRONTAB === true)
                $endTime = time() + 60; //Если на кроне, то работаем 60 секунд
            else
                $endTime = time() + 60;
            $obCacheCleaner = new CFileCacheCleaner("all");

            if (!$obCacheCleaner->InitPath($path)) {
                //Произошла ошибка
                return "CacheCleaner::cleanExpireCache();";
            }

            $obCacheCleaner->Start();

            while ($file = $obCacheCleaner->GetNextFile()) {
                if($file == 1)
                    continue;

                if (is_string($file)) {
                    if (time() >= $endTime)
                        break;
                    $lastFile = $file;
                    $date_expire = $obCacheCleaner->GetFileExpiration($file);
                    if ($date_expire) {
                        if ($date_expire < $curentTime) {
                            unlink($file);
                        }
                    }
                }
            }

            if (is_string($lastFile)) {
                return "CacheCleaner::cleanExpireCache(\"" . substr($lastFile, strpos($lastFile, "/bitrix/cache")) . "\");";
            } else {
                return "CacheCleaner::cleanExpireCache();";
            }
        }
    }
}