<?php
namespace Custom\Main\Entity;

use Bitrix\Main\Loader;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;

class PropertyTable extends ElementTable
{
    public static function getTableName()
    {
        return 'b_iblock_element_property';
    }

    public static function getMap()
    {
        Loader::includeModule('iblock');
        $elementClass = '\Bitrix\Iblock\ElementTable';

        $arMap = array(
            "ID" => array(
                "data_type" => "integer",
                "primary" => true,
                "autocomplete" => true
            ),
            "IBLOCK_ELEMENT_ID" => array(
                "data_type" => "integer",
                "primary" => true
            ),
            'IBLOCK_ELEMENT' => array(
                "data_type" => $elementClass,
                "reference" => array(
                    "=this.IBLOCK_ELEMENT_ID" => "ref.ID"
                )
            ),
            "IBLOCK_PROPERTY_ID" => array(
                "data_type" => "integer"
            ),
            "VALUE" => array(
                "data_type" => "string"
            ),
            "DESCRIPTION" => array(
                "data_type" => "string"
            ),
            "VALUE_ENUM" => array(
                "data_type" => "integer"
            ),
            "VALUE_NUM" => array(
                "data_type" => "float"
            ),
            'LINK_IBLOCK_ELEMENT' => array(
                'data_type' => $elementClass,
                'reference' => array(
                    '=this.VALUE' => 'ref.ID'
                )
            ),
            'LINK_VALUE_ENUM' => array(
                'data_type' => '\Bitrix\Iblock\PropertyEnumerationTable',
                'reference' => array(
                    '=this.VALUE_ENUM' => 'ref.ID'
                )
            )
        );
        return $arMap;
    }

    /**
     * Возвращает ID свойства по символьному коду
     *
     * @param string $code
     * @return int
     * @throws NotImplementedException
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     */
    public static function getPropertyId($iblockId, $code)
    {
        $properties = array();
        $obCache = new \CPHPCache;
        $cacheId = md5(get_called_class() . " ::" . __FUNCTION__ . implode('|', func_get_args()));
        $cacheDir = '/modules/custom.main/getPropertyId/';
        if ($obCache->InitCache(36000, $cacheId, $cacheDir))
        {
            $vars = $obCache->GetVars();
            $properties = $vars['properties'];
        }
        elseif (Loader::includeModule('iblock') && $obCache->StartDataCache())
        {
            if ($properties = \Bitrix\Iblock\PropertyTable::getList(array(
                'filter' => array(
                    'IBLOCK_ID' => $iblockId
                )
            ))->fetchAll())
            {
                $obCache->EndDataCache(array('properties' => $properties));
            }
            else
            {
                $obCache->AbortDataCache();
            }
        }

        foreach ($properties as $property)
        {
            if ($property['CODE'] == $code)
            {
                return (int)$property['ID'];
            }
        }

        return 0;
    }
}