<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

try
{
	$arComponentParameters = CComponentUtil::GetComponentProps('ds:base.component', $arCurrentValues);
}
catch (Main\SystemException $e)
{
	ShowError($e->getMessage());
}

$arSort = CIBlockParameters::GetElementSortFields(
  array('SORT', 'TIMESTAMP_X', 'NAME', 'ID'),
  array('KEY_LOWERCASE' => 'Y')
);

$arComponentParameters["PARAMETERS"]["CHECK_DATES"] = array(
	"PARENT" => "DATA_SOURCE",
	"NAME" => Loc::getMessage("BASE_CHECK_DATES"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "Y",
);
$arComponentParameters["PARAMETERS"]["ELEMENT_ID"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("BASE_IBLOCK_ELEMENT_ID"),
	"TYPE" => "STRING",
	"DEFAULT" => '={$_REQUEST["ELEMENT_ID"]}',
);
$arComponentParameters["PARAMETERS"]["ELEMENT_CODE"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("BASE_IBLOCK_ELEMENT_CODE"),
	"TYPE" => "STRING",
	"DEFAULT" => '={$_REQUEST["ELEMENT_CODE"]}',
);
$arComponentParameters["PARAMETERS"]["ACTIVE_DATE_FORMAT"] = CIBlockParameters::GetDateFormat(Loc::getMessage("BASE_ACTIVE_DATE_FORMAT"), "VISUAL");
$arComponentParameters["PARAMETERS"]["FIELD_CODE"] = CIBlockParameters::GetFieldCode(Loc::getMessage("BASE_IBLOCK_FIELD"), "DATA_SOURCE");
$arComponentParameters["PARAMETERS"]["SET_TITLE"] = Array();
