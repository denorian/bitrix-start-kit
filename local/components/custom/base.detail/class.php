<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CBitrixComponent::includeComponentClass("custom:base.component");

class BaseDetailComponent extends BaseComponent
{
    public function onPrepareComponentParams($params)
    {
        $params = parent::onPrepareComponentParams($params);

        $params["ADD_SECTIONS_CHAIN"] = $params["ADD_SECTIONS_CHAIN"]!="N"; //Turn on by default
        $params["ADD_ELEMENT_CHAIN"] = (isset($params["ADD_ELEMENT_CHAIN"]) && $params["ADD_ELEMENT_CHAIN"] == "Y");
        $params["CHECK_DATES"] = $params["CHECK_DATES"]!="N";
        $params["ACTIVE_DATE_FORMAT"] = trim($params["ACTIVE_DATE_FORMAT"]);

        $params["ELEMENT_ID"] = intval($params["ELEMENT_ID"]);
        $params["ELEMENT_CODE"] = trim($params["ELEMENT_CODE"]);

        return $params;
    }

    /**
     * Prepares $this->filter
     * @return void
     */
    public function prepareFilter()
    {
        $this->filter = array(
            "IBLOCK_TYPE" => $this->arParams["IBLOCK_TYPE"],
            "IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
            "IBLOCK_ACTIVE" => "Y",
            "ACTIVE" => "Y",
        );

        if($this->arParams["ELEMENT_ID"] > 0)
        {
            $this->filter["ID"] = $this->arParams["ELEMENT_ID"];
        }
        elseif(strlen($this->arParams["ELEMENT_CODE"]) > 0)
        {
            $this->filter["=CODE"] = $this->arParams["ELEMENT_CODE"];
        }

        if($this->arParams["CHECK_DATES"])
            $this->filter["ACTIVE_DATE"] = "Y";

    }

    public function getData()
    {
        $this->prepareFilter();

        $iterator = \CIBlockElement::GetList(array(), $this->filter, false, false, $this->selectFields);
        if($ob = $iterator->GetNextElement())
        {
            $this->arResult = $ob->GetFields();
            $this->arResult["PROPS"] = $ob->GetProperties();

            if($this->arResult["IBLOCK_SECTION_ID"] > 0)
            {
                $filter = array(
                    "ID" => $this->arResult["IBLOCK_SECTION_ID"],
                    "IBLOCK_ID" => $this->arResult["IBLOCK_ID"],
                    "ACTIVE" => "Y",
                );
                $this->arResult["SECTION"] = CIBlockSection::GetList(Array(),$filter)->GetNext();
            }
            if($this->arResult["SECTION"])
            {
                $this->arResult["SECTION"]["PATH"] = array();
                $pathIterator = CIBlockSection::GetNavChain($this->arResult["IBLOCK_ID"], $this->arResult["SECTION"]["ID"]);
                while($path = $pathIterator->GetNext())
                {
                    $ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($this->arResult["IBLOCK_ID"], $path["ID"]);
                    $path["IPROPERTY_VALUES"] = $ipropValues->getValues();
                    $this->arResult["SECTION"]["PATH"][] = $path;
                }
            }

            $ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($this->arResult["IBLOCK_ID"], $this->arResult["ID"]);
            $this->arResult["IPROPERTY_VALUES"] = $ipropValues->getValues();
        }
        else
        {
            $this->abortDataCache();
            @define("ERROR_404", "Y");
            return;
        }

        $this->prepareResult();

		$this->arResult = array_merge($this->arResult, $this->prepareErmitage($this->arResult['IBLOCK_ID'],$this->arResult['ID']));
		$this->getSeo();
		$this->setElementMeta($this->arResult['IPROPERTY_VALUES']);
        $this->setOgTags($this->arResult);

        if (!empty($this->cacheKeys))
        {
            $this->setResultCacheKeys(array('OG_TAGS'));
        }
    }

    public function executeEpilog()
    {
        $this->setOgTagsEpilog();
        $this->prepareBreadcrumbs();
        return $this->arResult['ID'];
    }

    public function setOgTagsEpilog()
    {
        GLOBAL $APPLICATION;

        if (!empty($this->arResult['OG_TAGS']['TITLE']))
        {
            $APPLICATION->AddHeadString('<meta property="og:title" content="'.$this->arResult['OG_TAGS']['TITLE'].'"/> ');
        }

        if (!empty($this->arResult['OG_TAGS']['DESCRIPTION']))
        {
            $APPLICATION->AddHeadString('<meta property="og:description" content="'.$this->arResult['OG_TAGS']['DESCRIPTION'].'"/> ');
        }

        if (!empty($this->arResult['OG_TAGS']['IMAGE']['SRC']))
        {
            $APPLICATION->AddHeadString('<meta property="og:image" content="'.'http://' . $_SERVER['SERVER_NAME'] . $this->arResult['OG_TAGS']['IMAGE']['SRC'].'"/> ');
        }

        if (!empty($this->arResult['OG_TAGS']['URL']))
        {
            $APPLICATION->AddHeadString('<meta property="og:url" content="'.$this->arResult['OG_TAGS']['URL'].'"/> ');
        }
    }

    /**
     * ���������� ������� ���������
     */
    public function prepareBreadcrumbs()
    {
        if ($this->arParams['ADD_SECTIONS_CHAIN'])
        {
            $this->addSectionsChain();

        }
        if ($this->arParams['ADD_ELEMENT_CHAIN'])
        {
            $this->addElementChain();
        }
    }

    /**
     * ���������� ������� ��������
     */
    public function addSectionsChain()
    {
        global $APPLICATION;
        if (!empty($this->arResult['SECTION']))
        {
            foreach ($this->arResult['SECTION']['PATH'] as $section)
            {
                $APPLICATION->AddChainItem($section['NAME'], $section['SECTION_PAGE_URL']);
            }
        }
    }

    /**
     * ������� �������� � ������� ���������
     */
    public function addElementChain()
    {
        global $APPLICATION;
        $APPLICATION->AddChainItem($this->arResult['NAME'], $this->arResult['DETAIL_PAGE_URL']);
    }
}
