<?php
namespace Custom\Main\Entity;

use Bitrix\Main\Loader;
use Bitrix\Main\NotImplementedException;

Loader::includeModule('iblock');

class SectionTable extends \Bitrix\Iblock\SectionTable
{
    /**
     * @abstract
     * @return string
     * @throws \Bitrix\Main\NotImplementedException
     */
    public static function getUfId()
    {
        throw new NotImplementedException('Method getUfId() must be implemented by successor.');
    }

    public static function getIblockId()
    {
        $className = get_called_class();
        $className = preg_replace('/SectionTable$/', 'ElementTable', $className);
        if (class_exists($className)) {
            return $className::getIblockId();
        }
        return 0;
    }
}