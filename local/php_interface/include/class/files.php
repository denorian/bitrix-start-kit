<?php

/**
 * Class Files
 * @package Mega\Main\Helpers
 */
class Files
{
    /**
     * @param $fileID
     * @return mixed
     */
    public static function getDownloadFileUrl($fileID)
    {
        return '/local/tools/download.php?file_id='.$fileID.'&key='.md5($fileID.'_secure');
    }
}
