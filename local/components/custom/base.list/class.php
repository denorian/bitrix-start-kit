<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CBitrixComponent::includeComponentClass("custom:base.component");

class BaseListComponent extends BaseComponent
{

    public function onPrepareComponentParams($params)
    {
        $params = parent::onPrepareComponentParams($params);

        /* Sorting */
        if (strlen($params["SORT_BY1"]) <= 0)
            $params["SORT_BY1"] = "SORT";
        if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $params["SORT_ORDER1"]))
            $params["SORT_ORDER1"] = "ASC";

        if (strlen($params["SORT_BY2"]) <= 0)
            $params["SORT_BY2"] = "id";
        if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $params["SORT_ORDER2"]))
            $params["SORT_ORDER2"] = "desc";

        $params["SHOW_ALL_WO_SECTION"] = $params["SHOW_ALL_WO_SECTION"] === "Y";
        $params["INCLUDE_SUBSECTIONS"] = in_array($params["INCLUDE_SUBSECTIONS"], array("Y", "A", "N")) ? $params["INCLUDE_SUBSECTIONS"] : "Y";
        $params["CHECK_DATES"] = $params["CHECK_DATES"] != "N";
        $params["ACTIVE_DATE_FORMAT"] = trim($params["ACTIVE_DATE_FORMAT"]);

        $params["SECTION_ID"] = intval($params["SECTION_ID"]);
        $params["SECTION_CODE"] = trim($params["SECTION_CODE"]);
        $params["SECTION_CODE_PATH"] = trim($params["SECTION_CODE_PATH"]);
        $params["ADD_SECTIONS_CHAIN"] = $params["ADD_SECTIONS_CHAIN"] != "N"; //Turn on by default

        $params = parent::prepareNavigation($params);

        return $params;
    }

    /**
     * Prepares $this->filter
     * @return void
     */
    public function prepareFilter()
    {
        $this->filter = array(
            "IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
            "IBLOCK_ACTIVE" => "Y",
            "ACTIVE" => "Y",
            "INCLUDE_SUBSECTIONS" => ($this->arParams["INCLUDE_SUBSECTIONS"] == 'N' ? 'N' : 'Y'),
        );

        if (!empty($this->arParams['IBLOCK_TYPE']))
            $this->filter['IBLOCK_TYPE'] = $this->arParams['IBLOCK_TYPE'];

        if (!empty($this->arParams['SECTION_ID']))
            $this->filter['SECTION_ID'] = $this->arParams['SECTION_ID'];
        elseif (!empty($this->arParams['SECTION_CODE']))
            $this->filter['SECTION_CODE'] = $this->arParams['SECTION_CODE'];

        if ($this->arParams["INCLUDE_SUBSECTIONS"] == 'A')
            $this->filter["SECTION_GLOBAL_ACTIVE"] = "Y";

        if ($this->arParams["CHECK_DATES"])
            $this->filter["ACTIVE_DATE"] = "Y";

        if ($this->arParams['FILTER_NAME']) {
            $extFilter = $GLOBALS[$this->arParams['FILTER_NAME']];

            if (is_array($extFilter)) {
                $this->filter = array_merge($this->filter, $extFilter);
            }

            $this->addCacheAdditionalId($extFilter);
        }
    }

    public function getData()
    {
        $bSectionFound = false;

        if ($this->arParams["SECTION_ID"] > 0) {
            $this->arResult["SECTION"] = \CIBlockSection::GetList(array(), array("ID" => $this->arParams["SECTION_ID"], "IBLOCK_ID" => $this->arParams["IBLOCK_ID"]), false, array('UF_*'))->GetNext();
            if ($this->arResult["SECTION"]) $bSectionFound = true;
        } elseif (strlen($this->arParams["SECTION_CODE"]) > 0) {
            $this->arResult["SECTION"] = \CIBlockSection::GetList(array(), array("=CODE" => $this->arParams["SECTION_CODE"], "IBLOCK_ID" => $this->arParams["IBLOCK_ID"]), false, array('UF_*'))->GetNext();
            if ($this->arResult["SECTION"]) $bSectionFound = true;
        } elseif (strlen($this->arParams["SECTION_CODE_PATH"]) > 0) {
            $bSectionFound = false;
        } else {
            $bSectionFound = true;
        }

        if (!$bSectionFound) {
            $this->abortDataCache();
            @define("ERROR_404", "Y");
            return;
        }

        if (is_array($this->arResult["SECTION"])) {
            $this->arResult["SECTION"]["PATH"] = array();
            $pathIterator = \CIBlockSection::GetNavChain($this->arParams["IBLOCK_ID"], $this->arResult["SECTION"]["ID"]);
            while ($path = $pathIterator->GetNext()) {
                if ($path["ID"] != $this->arResult["SECTION"]["ID"]) {
                    $ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($this->arParams["IBLOCK_ID"], $path["ID"]);
                    $path["IPROPERTY_VALUES"] = $ipropValues->getValues();
                    $this->arResult["SECTION"]["PATH"][] = $path;
                }
            }

            $ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($this->arResult["SECTION"]["IBLOCK_ID"], $this->arResult["SECTION"]["ID"]);
            $this->arResult["SECTION"]["IPROPERTY_VALUES"] = $ipropValues->getValues();
        }

        if ($this->arResult["SECTION"]["ID"]) {
            $this->filter["SECTION_ID"] = $this->arResult["SECTION"]["ID"];
        }
        $iterator = \CIBlockElement::GetList($this->sort, $this->filter, false, $this->navParams, $this->selectFields);
        $this->arResult["ITEMS"] = array();
        while ($ob = $iterator->GetNextElement()) {
            $item = $ob->GetFields();

            $item = array_merge($item, $this->prepareErmitage($item['IBLOCK_ID'], $item['ID']));

            if (empty($this->selectFields)) {
                $item["PROPS"] = $ob->GetProperties();
            }

            $this->setOgTags($item);
            $this->arResult["ITEMS"][] = $this->prepareItem($item);
            $strtotime = strtotime($item['TIMESTAMP_X']);
            if ($strtotime > $this->arResult[self::RESULT_LAST_MODIFIED]) {
                $this->arResult[self::RESULT_LAST_MODIFIED] = $strtotime;
            }
        }
        if ($this->arParams["DISPLAY_TOP_PAGER"] || $this->arParams["DISPLAY_BOTTOM_PAGER"])
            $this->arResult["NAV_STRING"] = $iterator->GetPageNavStringEx($navComponentObject, $this->arParams["PAGER_TITLE"], $this->arParams["PAGER_TEMPLATE"], $this->arParams["PAGER_SHOW_ALWAYS"]);

        $this->prepareResult();
        $this->arResult['NAV_RESULT'] = $iterator;
    }

    public function prepareItem($item)
    {
        return $item;
    }

    public function executeEpilog()
    {
        $this->prepareBreadcrumbs();
    }

    /**
     * prepare chain navigation
     */
    public function prepareBreadcrumbs()
    {
        if ($this->arParams['ADD_SECTIONS_CHAIN']) {
            $this->addSectionsChain();
        }
    }

    /**
     * add to chain navigation
     */
    public function addSectionsChain()
    {
        global $APPLICATION;
        if ($this->arParams['ADD_SECTIONS_CHAIN']) {
            if (!empty($this->arResult['SECTION'])) {
                foreach ($this->arResult['SECTION']['PATH'] as $section) {
                    $APPLICATION->AddChainItem($section['NAME'], $section['SECTION_PAGE_URL']);
                }

                $APPLICATION->AddChainItem($this->arResult['SECTION']['NAME'], $this->arResult['SECTION']['SECTION_PAGE_URL']);
            }
        }
    }
}