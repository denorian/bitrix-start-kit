<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */
use \Bitrix\Main\Localization\Loc as Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters["PARAMETERS"] = array(
    "SEF_MODE" => array(
        "main" => array(
            "NAME" => "Главная",
            "DEFAULT" => "index.php",
            "VARIABLES" => array(),
        ),
        "section" => array(
            "NAME" => "Страница раздела",
            "DEFAULT" => "#SECTION_CODE#/",
            "VARIABLES" => array(),
        ),
        "detail" => array(
            "NAME" => "Детальная страница",
            "DEFAULT" => "#SECTION_CODE#/#ELEMENT_CODE#.php",
            "VARIABLES" => array(),
        ),
        "tag" => array(
            "NAME" => "Страница тега",
            "DEFAULT" => "tag/#TAG_CODE#/",
            "VARIABLES" => array(),
        ),
        "rss" => array(
            "NAME" => "RSS",
            "DEFAULT" => "rss/",
            "VARIABLES" => array(),
        ),
    ),
);