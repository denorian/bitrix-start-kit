<?php
namespace Custom\Main\Base\Article;

class SectionTable extends \Custom\Main\Entity\SectionTable
{
    public static function getUfId()
    {
        return sprintf('IBLOCK_%d_SECTION', ElementTable::getIblockId());
    }
}