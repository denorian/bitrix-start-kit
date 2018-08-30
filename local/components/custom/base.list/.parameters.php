<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */
use \Bitrix\Main as Main;
use \Bitrix\Main\Localization\Loc as Loc;

Loc::loadMessages(__FILE__);

if(!Main\Loader::includeModule("iblock")) return;

$component = "base.component";
$documentRoot = Main\Application::getDocumentRoot();
$fname = Main\Loader::getLocal("components/ds/".$component."/.parameters.php", $documentRoot);


$order = array(
    "asc" => Loc::getMessage("BASE_IBLOCK_SORT_ASC"),
    "desc" => Loc::getMessage("BASE_IBLOCK_SORT_DESC"),
);

$sort = CIBlockParameters::GetElementSortFields(
    array('SHOWS', 'SORT', 'TIMESTAMP_X', 'NAME', 'ID', 'ACTIVE_FROM', 'ACTIVE_TO'),
    array('KEY_LOWERCASE' => 'Y')
);

include($fname);

$arComponentParametersAdd["PARAMETERS"] = array(
    "SECTION_ID" => array(
        "PARENT" => "BASE",
        "NAME" => GetMessage("BASE_IBLOCK_SECTION_ID"),
        "TYPE" => "STRING",
        "DEFAULT" => '={$_REQUEST["SECTION_ID"]}',
    ),
    "SECTION_CODE" => array(
        "PARENT" => "BASE",
        "NAME" => GetMessage("BASE_IBLOCK_SECTION_CODE"),
        "TYPE" => "STRING",
        "DEFAULT" => '={$_REQUEST["SECTION_CODE"]}',
    ),
    "SORT_BY1" => array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => Loc::getMessage("BASE_IBLOCK_SORT_FIELD"),
        "TYPE" => "LIST",
        "VALUES" => $sort,
        "ADDITIONAL_VALUES" => "Y",
        "DEFAULT" => "sort",
    ),
    "SORT_ORDER1" => array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => Loc::getMessage("BASE_IBLOCK_SORT_ORDER"),
        "TYPE" => "LIST",
        "VALUES" => $order,
        "DEFAULT" => "asc",
        "ADDITIONAL_VALUES" => "Y",
    ),
    "SORT_BY2" => array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => Loc::getMessage("BASE_IBLOCK_SORT_FIELD2"),
        "TYPE" => "LIST",
        "VALUES" => $sort,
        "ADDITIONAL_VALUES" => "Y",
        "DEFAULT" => "id",
    ),
    "SORT_ORDER2" => array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => Loc::getMessage("BASE_IBLOCK_SORT_ORDER2"),
        "TYPE" => "LIST",
        "VALUES" => $order,
        "DEFAULT" => "desc",
        "ADDITIONAL_VALUES" => "Y",
    ),
    "INCLUDE_SUBSECTIONS" => array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => Loc::getMessage("BASE_INCLUDE_SUBSECTIONS"),
        "TYPE" => "LIST",
        "VALUES" => array(
            "Y" => Loc::getMessage('BASE_INCLUDE_SUBSECTIONS_ALL'),
            "A" => Loc::getMessage('BASE_INCLUDE_SUBSECTIONS_ACTIVE'),
            "N" => Loc::getMessage('BASE_INCLUDE_SUBSECTIONS_NO'),
        ),
        "DEFAULT" => "Y",
    ),
    "SHOW_ALL_WO_SECTION" => array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => Loc::getMessage("BASE_SHOW_ALL_WO_SECTION"),
        "TYPE" => "CHECKBOX",
        "DEFAULT" => "N",
    ),
    "PAGE_ELEMENT_COUNT" => array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => Loc::getMessage("BASE_IBLOCK_PAGE_ELEMENT_COUNT"),
        "TYPE" => "STRING",
        "DEFAULT" => "30",
    ),
    "CHECK_DATES" => array(
        "PARENT" => "DATA_SOURCE",
        "NAME" => Loc::getMessage("BASE_CHECK_DATES"),
        "TYPE" => "CHECKBOX",
        "DEFAULT" => "Y",
    ),
    "ACTIVE_DATE_FORMAT" => CIBlockParameters::GetDateFormat(Loc::getMessage("BASE_ACTIVE_DATE_FORMAT"), "VISUAL"),
    "ADD_SECTIONS_CHAIN" => array(
        "PARENT" => "ADDITIONAL_SETTINGS",
        "NAME" => Loc::getMessage("BASE_ADD_SECTIONS_CHAIN"),
        "TYPE" => "CHECKBOX",
        "DEFAULT" => "N",
    ),
    "SET_TITLE" => Array(),
    "FIELD_CODE" => CIBlockParameters::GetFieldCode(Loc::getMessage("BASE_IBLOCK_FIELD"), "DATA_SOURCE"),
);
$arComponentParameters["PARAMETERS"] = array_merge($arComponentParameters["PARAMETERS"], $arComponentParametersAdd["PARAMETERS"]);

CIBlockParameters::AddPagerSettings($arComponentParameters, Loc::getMessage("BASE_DESC_PAGER"), false, false);