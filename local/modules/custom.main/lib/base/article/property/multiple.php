<?php
namespace Custom\Main\Base\Article\Property;

use Custom\Main\Base\Article\ElementTable;

class MultipleTable extends \Custom\Main\Entity\Property\MultipleTable
{
    public static function getIblockCode()
    {
        return ElementTable::getIblockCode();
    }
}