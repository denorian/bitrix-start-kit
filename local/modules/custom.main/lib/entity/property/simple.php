<?php
namespace Custom\Main\Entity\Property;

use Bitrix\Main\Loader;
use Custom\Main\Entity\PropertyTable;

class SimpleTable extends PropertyTable
{
    public static function getTableName()
    {
        return 'b_iblock_element_prop_s' . static::getIblockId();
    }

    public static function getIblockId()
    {
        return parent::getIblockId(static::getIblockCode(), 2);
    }

    public static function getPropertyId($code)
    {
        return parent::getPropertyId(
            parent::getIblockId(static::getIblockCode()),
            $code
        );
    }

    public static function getMap()
    {
        if (class_exists(str_replace('Property\\Simple', 'Element', get_called_class())))
        {
            $elementClass = str_replace('Property\\Simple', 'Element', get_called_class());
        }
        else
        {
            Loader::includeModule('iblock');
            $elementClass = '\Bitrix\Iblock\ElementTable';
        }

        $arMap = array(
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'primary' => true
            ),
            'IBLOCK_ELEMENT' => array(
                "data_type" => $elementClass,
                "reference" => array(
                    "=this.IBLOCK_ELEMENT_ID" => "ref.ID"
                )
            )
        );
        $arMap = array_merge($arMap, static::getPropertyMap());
        return $arMap;
    }

    private static function getPropertyMap()
    {
        global $CACHE_MANAGER;
        $obCache = new \CPHPCache;
        $cacheId = md5(get_called_class() . "::" . __METHOD__);
        $cacheDir = '/modules/custom.main/getPropertyMap/';
        $arProperties = array();
        if ($obCache->InitCache(36000, $cacheId, $cacheDir))
        {
            $vars = $obCache->GetVars();

            $arProperties = $vars["arProperties"];
        }
        elseif (Loader::includeModule('iblock') && $obCache->StartDataCache())
        {
            $arFilter = array(
                "IBLOCK_ID" => static::getIblockId(),
                "MULTIPLE" => "N"
            );
            $rsProperty = \CIBlockProperty::GetList(
                array(),
                $arFilter
            );
            while ($arProperty = $rsProperty->Fetch())
            {
                if (empty($arProperty["CODE"]))
                {
                    continue;
                }

                $arColumn = array(
                    'column_name' => 'PROPERTY_' . $arProperty['ID']
                );
                switch ($arProperty["PROPERTY_TYPE"])
                {
                    case 'L':
                    case 'F':
                    case 'G':
                    case 'E':
                    case 'S:UserID':
                    case 'E:EList':
                    case 'S:FileMan':
                        $arColumn["data_type"] = "integer";
                        break;

                    case 'S:DateTime':
                        $arColumn["data_type"] = "datetime";
                        break;

                    case 'N':
                        $arColumn["data_type"] = "float";
                        break;
                    case 'S':
                    default:
                        $arColumn["data_type"] = "string";

                        if ($arProperty["USER_TYPE"] == "HTML")
                        {
                            $arColumn["data_type"] = "text";
                            $arColumn["serialized"] = true;
                        }

                        break;
                }

                $arProperties[$arProperty["CODE"]] = $arColumn;
            }

            $CACHE_MANAGER->StartTagCache($cacheDir);
            $CACHE_MANAGER->RegisterTag("property_iblock_id_" . static::getIblockId());
            $CACHE_MANAGER->EndTagCache();

            $obCache->EndDataCache(array("arProperties" => $arProperties));
        }

        return $arProperties;
    }
}