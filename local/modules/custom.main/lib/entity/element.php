<?php
namespace Custom\Main\Entity;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Main\NotImplementedException;

Loader::includeModule('iblock');

class ElementTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return \Bitrix\Iblock\ElementTable::getTableName();
    }

    public static function getMap()
    {
        $result = \Bitrix\Iblock\ElementTable::getMap();

        $result['PREVIEW_PICTURE_FILE'] = new ReferenceField(
            'PREVIEW_PICTURE_FILE',
            FileTable::getEntity(),
            array(
                '=ref.ID' => 'this.PREVIEW_PICTURE'
            )
        );

        $result['DETAIL_PICTURE_FILE'] = new ReferenceField(
            'DETAIL_PICTURE_FILE',
            FileTable::getEntity(),
            array(
                '=ref.ID' => 'this.DETAIL_PICTURE'
            )
        );

        return $result;
    }

    /**
     * Возвращает ID информационного блока по коду
     *
     * @param string $code код информационного блока
     * @param bool $version номер версии ИБ
     * @return int
     * @throws \Bitrix\Main\LoaderException
     */
    public static function getIblockId($code, $version = false)
    {
        $result = false;

        $obCache = new \CPHPCache;
        $cacheId = md5(get_called_class() . '::' . __FUNCTION__ . implode('|', func_get_args()));
        $cacheDir = '/modules/custom.main/getIblockId/';
        if ($obCache->InitCache(36000, $cacheId, $cacheDir)) {
            $vars = $obCache->GetVars();
            $result = $vars['iblock_id'];
        } elseif ($obCache->StartDataCache()) {
            $filter = array(
                '=CODE' => $code
            );
            if ($version) {
                $filter['=VERSION'] = $version;
            }
            if ($arIblock = IblockTable::getList(array(
                'select' => array('ID'),
                'filter' => $filter,
                'limit' => 1
            ))->fetch()) {
                $result = $arIblock['ID'];
                $obCache->EndDataCache(array('iblock_id' => $result));
            } else {
                $obCache->AbortDataCache();
            }
        }

        return $result;
    }

    /**
     * Возвращает ID элемента по символьному коду
     *
     * @param string $code символьный код элемента
     * @return int|bool
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function getIdByCode($code)
    {
        global $CACHE_MANAGER;

        $result = false;

        $obCache = new \CPHPCache;
        $cacheId = md5(get_called_class() . '::' . __FUNCTION__ . implode('|', func_get_args()));
        $cacheDir = '/modules/custom.main/getIdByCode/';

        if ($obCache->InitCache(36000, $cacheId, $cacheDir)) {
            $vars = $obCache->GetVars();
            $result = $vars['result'];
        } elseif ($obCache->StartDataCache()) {
            if ($element = ElementTable::getList(array(
                'filter' => array(
                    'IBLOCK_ID' => self::getIblockId(static::getIblockCode()),
                    '=CODE' => $code
                ),
                'select' => array(
                    'ID',
                    'IBLOCK_ID'
                )
            ))->fetch()) {
                $result = (int)$element['ID'];

                $CACHE_MANAGER->StartTagCache($cacheDir);
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $element['IBLOCK_ID']);
                $CACHE_MANAGER->EndTagCache();

                $obCache->EndDataCache(array('result' => $result));
            } else {
                $obCache->AbortDataCache();
            }
        }

        return $result;
    }

    /**
     * @abstract
     * @return string
     * @throws \Bitrix\Main\NotImplementedException
     */
    public static function getIblockCode()
    {
        throw new NotImplementedException("Method getIblockCode() must be implemented by successor.");
    }
}