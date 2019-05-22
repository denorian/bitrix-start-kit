<?php
namespace Custom\Main\Entity\Property;

use Bitrix\Main\Loader;
use Custom\Main\Entity\PropertyTable;

class MultipleTable extends PropertyTable
{

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

    public static function getTableName()
    {
        return 'b_iblock_element_prop_m' . static::getIblockId();
    }

    public static function getMap()
    {
        Loader::includeModule('iblock');
        $elementClass = '\Bitrix\Iblock\ElementTable';

        if (class_exists(str_replace('Property\\Multiple', 'Element', get_called_class())))
        {
            $elementClass = str_replace('Property\\Multiple', 'Element', get_called_class());
        }

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
            "VALUE_NUM" => array(
                "data_type" => "float"
            ),
            'LINK_IBLOCK_ELEMENT' => array(
                'data_type' => '\Bitrix\Iblock\ElementTable',
                'reference' => array(
                    '=this.VALUE' => 'ref.ID'
                )
            )
        );
        return $arMap;
    }
}