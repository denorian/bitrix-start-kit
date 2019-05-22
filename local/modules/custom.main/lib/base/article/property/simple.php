<?php
namespace Custom\Main\Base\Article\Property;

use Custom\Main\Base\Article\ElementTable;

class SimpleTable extends \Custom\Main\Entity\Property\SimpleTable
{
    public static function getIblockCode()
    {
        return ElementTable::getIblockCode();
    }
}