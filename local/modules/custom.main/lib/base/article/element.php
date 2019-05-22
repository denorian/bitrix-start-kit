<?php
namespace Custom\Main\Base\Article;

class ElementTable extends \Custom\Main\Entity\ElementTable
{
    public static function getIblockCode()
    {
        return 'article';
    }

    public static function getIblockId()
    {
        return parent::getIblockId(static::getIblockCode());
    }
}