<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main as Main;
use \Bitrix\Main\Localization\Loc as Loc;

/**
 * Class BaseComponent
 */

class BaseComponent extends CBitrixComponent
{
    const RESULT_LAST_MODIFIED = 'RESULT_LAST_MODIFIED';

    /**
     * Modules required
     * @var array[]
     */
    public $modules = array("iblock");

    /**
     * Navigation parameters
     * @var array[]
     */
    public $navParams = array();

    /**
     * @var bool
     */
    public $navigation = false;

    /**
     * Filter
     * @var array[]
     */
    public $filter = array();

    /**
     * Sort
     * @var array[]
     */
    public $sort = array();

    /**
     * Select fields
     * @var array[]
     */
    public $selectFields = array();

    /**
     * Нужно ли кешировать шаблон
     * @var bool
     */
    protected $cacheTemplate = true;

    /**
     * дополнительные параметры, от которых должен зависеть кеш
     * @var array
     */
    protected $cacheAdditionalId = array();

    /**
     * Кешируемые ключи
     * @var array
     */
    protected $cacheKeys = array();

    /**
     * Load language file
     */
    public function onIncludeComponentLang()
    {
        $this->includeComponentLang(basename(__FILE__));
        Loc::loadMessages(__FILE__);
    }

    /**
     * Prepare Component Params
     */
    public function onPrepareComponentParams($params)
    {
        $params = parent::onPrepareComponentParams($params);

        /* Iblock parameters */
        $params["IBLOCK_TYPE"] = trim($params["IBLOCK_TYPE"]);
		$params["IBLOCK_ID"] = intval($params["IBLOCK_ID"]);
		$params["IBLOCK_CODE"] = trim($params["IBLOCK_CODE"]);

        /* Caching */
        $params["CACHE_TYPE"] = in_array($params["CACHE_TYPE"], array("Y", "A", "N")) ? $params["CACHE_TYPE"] : "A";
        $params["CACHE_TIME"] = isset($params["CACHE_TIME"]) ? intval($params["CACHE_TIME"]) : 36000000;

        /* Using ajax mode */
        $params["AJAX_BLOCK"] = "bx_".CAjax::GetComponentID($this->getName(), $this->getTemplate(), array())."_ajax";
        $params["AJAX"] = $_REQUEST[$params["AJAX_BLOCK"]] === "Y";

        return $params;
    }

    /**
     * Check Required Modules
     * @throws Exception
     */
    public function checkModules()
    {
        foreach ($this->modules as $module)
        {
            if(!Main\Loader::includeModule($module))
            {
                throw new Main\LoaderException(Loc::getMessage("BASE_MODULE_NOT_INSTALLED", array("#MODULE#" => $module)));
            }
        }
    }

    /**
     * На данный момент функция обеспечивает поддержку кодов инфоблоков.
     * Ожидаются параметры вида:
     * IBLOCK_CODE, NEWS_IBLOCK_CODE, и т.д., также учитывается тип инфоблока, если он указан в параметрах (IBLOCK_TYPE, NEWS_IBLOCK_TYPE),
     * в результате в параметры будут установлены соответствующие идентификаторы, например IBLOCK_ID, NEWS_IBLOCK_ID
     */
    public function checkParams()
    {
       foreach ($this->arParams as $key => $value)
        {
            if (strpos($key, '~') === false && strpos($key, 'IBLOCK_CODE') !== false)
            {
				if (strlen($value) > 0)
				{
					$prefix = str_replace('IBLOCK_CODE', '', $key);
					$type = (!empty($this->arParams[$prefix . 'IBLOCK_TYPE']) ? $this->arParams[$prefix . 'IBLOCK_TYPE'] : '');
					$this->arParams[$prefix . 'IBLOCK_ID'] = $this->getIblockIdCached($value, $type);
				}
            }
        }
    }

    /**
     * Возвращает ID инфоблока по его коду [+ типу]
     * @param $iblockCode
     * @param string $iblockType
     * @return null
     * @throws Main\LoaderException
     */
    public function getIblockIdCached($iblockCode, $iblockType = '')
    {
        $result = false;

        if (!empty($iblockCode))
        {
            $filter = array('CODE' => $iblockCode);
            if (!empty($iblockType))
            {
                $filter['TYPE'] = $iblockType;
            }

            $cacheId = md5($this->__name . __FUNCTION__ . serialize($filter));
            $cacheDir = "/ds_components";

            $obCache = new CPHPCache();
            if ($obCache->InitCache($this->arParams['CACHE_TIME'], $cacheId, $cacheDir))
            {
                $result = $obCache->GetVars();
            }
            elseif (Main\Loader::includeModule('iblock') && $obCache->StartDataCache())
            {
                $iblock = CIBlock::GetList(array(), $filter)->Fetch();

                global $CACHE_MANAGER;
                $CACHE_MANAGER->StartTagCache($cacheDir);
                if ($iblock)
                {
                    $result = $iblock['ID'];
                }
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblock['ID']);
                $CACHE_MANAGER->EndTagCache();
                $obCache->EndDataCache($result);
            }
        }

        return $result;
    }

    /**
     * Prepares navigation
     * @return array
     */
    public function prepareNavigation($params)
    {
        $params["PAGE_ELEMENT_COUNT"] = intval($params["PAGE_ELEMENT_COUNT"]);
        $params["DISPLAY_TOP_PAGER"] = $params["DISPLAY_TOP_PAGER"]=="Y";
        $params["DISPLAY_BOTTOM_PAGER"] = $params["DISPLAY_BOTTOM_PAGER"]!="N";
        $params["PAGER_TITLE"] = trim($params["PAGER_TITLE"]);
        $params["PAGER_SHOW_ALWAYS"] = $params["PAGER_SHOW_ALWAYS"]!="N";
        $params["PAGER_TEMPLATE"] = trim($params["PAGER_TEMPLATE"]);
        $params["PAGER_SHOW_ALL"] = $params["PAGER_SHOW_ALL"]!=="N";

        if($params["DISPLAY_TOP_PAGER"] || $params["DISPLAY_BOTTOM_PAGER"])
        {
            $this->navParams = array(
                "nPageSize" => $params["PAGE_ELEMENT_COUNT"],
                "bShowAll" => $params["PAGER_SHOW_ALL"],
            );
            $this->navigation = CDBResult::GetNavParams($this->navParams);
        }
        else if($params["PAGE_ELEMENT_COUNT"])
        {
            $this->navParams = array(
                "nTopCount" => $params["PAGE_ELEMENT_COUNT"],
            );
            $this->navigation = false;
        }
        else
        {
            $this->navParams = false;
            $this->navigation = false;
        }

        return $params;
    }

    /**
     * Добавление дополнительного идентификатора от которого должен зависеть кеш
     *
     * @param mixed $id
     */
    protected function addCacheAdditionalId($id)
    {
        $this->cacheAdditionalId[] = $id;
    }

    /**
     * Prepares result
     * @return array
     */
    public function prepareResult()
    {

    }

	/**
	 * Prepare ermitage
	 * @return links
	 */
	public function prepareErmitage($iblockID, $elementID)
	{
		if ($iblockID > 0 && $elementID > 0)
		{
			$arButtons = \CIBlock::GetPanelButtons(
				$iblockID,
				$elementID,
				0,
				array("SECTION_BUTTONS" => false, "SESSID" => false)
			);

			return Array(
				"EDIT_LINK" => $arButtons["edit"]["edit_element"]["ACTION_URL"],
				"DELETE_LINK" => $arButtons["edit"]["delete_element"]["ACTION_URL"],
			);
		}
		else
		{
			return Array();
		}
	}

	/**
     * Prepares picture or resize
     * @return array
     */
    public function preparePicture($id, $width = false, $height = false, $type = BX_RESIZE_IMAGE_EXACT, $ratio = 2)
    {
        if($id)
        {
            $picture = CFile::GetFileArray($id);
            $description = $picture["DESCRIPTION"];

            if($width && $height)
            {
                $retina_ready = ($picture["WIDTH"] >= $width * $ratio && $picture["HEIGHT"] >= $height * $ratio);

                $picture = CFile::ResizeImageGet($id, array("width" => $width, "height" => $height), $type, true);

                if($retina_ready)
                {
                    $picture["RETINA"] = CFile::ResizeImageGet($id, array("width" => $width * $ratio, "height" => $height * $ratio), $type, true);
                }
            }
            $picture["DESCRIPTION"] = $description;

            return $picture;
        }
        else
        {
            return false;
        }
    }


    /**
     * Returns inherited seo properties for iblock
     * @param $iblockId
     * @return array
     */
    public static function getIblockSeo($iblockId)
    {
        $ipropValues = new \Bitrix\Iblock\InheritedProperty\IblockValues($iblockId);
        return $ipropValues->getValues();
    }

    /**
     * Returns inherited seo properties for section
     * @param $iblockId
     * @param $sectionId
     * @return array
     */
    public static function getSectionSeo($iblockId, $sectionId)
    {
        $ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($iblockId, $sectionId);
        return $ipropValues->getValues();
    }

    /**
     * Returns inherited seo properties for element
     * @param $iblockId
     * @param $elementId
     * @return array
     */
    public static function getElementSeo($iblockId, $elementId)
    {
        $iprops = new \Bitrix\Iblock\InheritedProperty\ElementValues($iblockId, $elementId);
        $ipropValues = $iprops->getValues();

        return $ipropValues;
    }

    public function show404()
    {
        $this->abortDataCache();
        @define("ERROR_404", "Y");
    }

    /**
     * Returns array with tags
     *
     * @param string $string String with tags a comma-separated
     * @return array
     */
    public static function getTagsArray($string)
    {
        $tags = explode(',', $string);

        foreach ($tags as &$tag)
        {
            $tag = trim($tag);
        }

        unset($tag);

        return array_diff($tags, array(null));
    }


    /**
     * Plural
     * @return string
     */
    public function plural($n, $form1, $form2, $form5)
    {
        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) return $form5;
        if ($n1 > 1 && $n1 < 5) return $form2;
        if ($n1 == 1) return $form1;

        return $form5;
    }

    /**
     * Prepares $this->sort
     * @return void
     */
    public function prepareSort()
    {
        $this->sort = array(
            $this->arParams["SORT_BY1"] => $this->arParams["SORT_ORDER1"],
            $this->arParams["SORT_BY2"] => $this->arParams["SORT_ORDER2"],
        );
    }

    public function prepareFilter()
    {
    }

    /**
     * Prepares dates
     * @return string
     */
    protected function prepareDate($date, $format = false)
    {
        if(strlen($date) > 0)
            $date = CIBlockFormatProperties::DateFormat($format ? $format : $this->arParams["ACTIVE_DATE_FORMAT"], MakeTimeStamp($date, CSite::GetDateFormat()));

        return $date;
    }

    /**
     * Set section meta
     * @return string
     */
    protected function setSectionMeta($meta)
    {
        global $APPLICATION;

        if($meta["SECTION_META_TITLE"] != "")
        {
            $APPLICATION->SetPageProperty("title", $meta["SECTION_META_TITLE"]);
        }

        if($meta["SECTION_META_KEYWORDS"] != "")
        {
            $APPLICATION->SetPageProperty("keywords", $meta["SECTION_META_KEYWORDS"]);
        }

        if($meta["SECTION_META_DESCRIPTION"] != "")
        {
            $APPLICATION->SetPageProperty("description", $meta["SECTION_META_DESCRIPTION"]);
        }
    }

    /**
     * Set element meta
     * @return string
     */
    protected function setElementMeta($meta)
    {
        global $APPLICATION;

        if($meta["ELEMENT_META_TITLE"] != "")
        {
            $APPLICATION->SetPageProperty("title", $meta["ELEMENT_META_TITLE"]);
        }

        if($meta["ELEMENT_META_KEYWORDS"] != "")
        {
            $APPLICATION->SetPageProperty("keywords", $meta["ELEMENT_META_KEYWORDS"]);
        }

        if($meta["ELEMENT_META_DESCRIPTION"] != "")
        {
            $APPLICATION->SetPageProperty("description", $meta["ELEMENT_META_DESCRIPTION"]);
        }
    }

    /**
     * Get main data
     * @return void
     */
    public function getData()
    {

    }

    /**
     * Actions
     * @return void
     */
    public function actions()
    {

    }

    /**
     * Set title and meta
     * @return mixed
     */
    public function executeEpilog()
    {
        return true;
    }

    /**
     * Extract data from cache.
     * @return bool
     */
    public function extractDataFromCache()
    {
        if($this->arParams["CACHE_TYPE"] == "N")
            return false;

        $this -> cacheAdditionalId = array_merge($this -> cacheAdditionalId, $this->navigation);

        return !($this->startResultCache(false, $this->cacheAdditionalId));
    }

    /**
     *
     */
    public function putDataToCache()
    {
        $this->endResultCache();
    }

    /**
     *
     */
    public function abortDataCache()
    {
        $this->abortResultCache();
    }

    /**
     * @return mixed
     */
    public function isAjax()
    {
        return $this->arParams["AJAX"];
    }

    protected function getSeo()
    {
        if (empty($this->arParams['IBLOCK_ID']) || $this->arParams['SET_TITLE'] != 'Y')
            return;

        if (intval($this->arResult['ID']) > 0)
        {
            $this->arResult['IPROPERTY_VALUES'] = $this->getElementSeo($this->arParams['IBLOCK_ID'], $this->arResult['ID']);
        }
        elseif (intval($this->arParams['SECTION_ID']) > 0)
        {
            $this->arResult['IPROPERTY_VALUES'] = $this->getSectionSeo($this->arParams['IBLOCK_ID'], $this->arParams['SECTION_ID']);
        }
        elseif (!empty($this->arResult["SECTION"]["ID"]))
        {
            $this->arResult['IPROPERTY_VALUES'] = $this->getSectionSeo($this->arParams['IBLOCK_ID'], $this->arResult["SECTION"]["ID"]);
        }
        else
        {
            $this->arResult['IPROPERTY_VALUES'] = $this->getIblockSeo($this->arParams['IBLOCK_ID']);
        }
    }

    protected function setOgTags(&$item)
    {
    }

    /**
     * Start Component
     */
    public function executeComponent()
    {
        global $APPLICATION;

        try
        {
            if($this->isAjax()) { $APPLICATION->RestartBuffer(); }

            $this->checkModules();
            $this->checkParams();
            $this->actions();
            $this->prepareSort();
            $this->prepareFilter();

            if (!$this->extractDataFromCache())
            {
                $this->getData();

                if (!empty($this->cacheKeys))
                {
                    $this->setResultCacheKeys($this->cacheKeys);
                }

                if ($this->cacheTemplate)
                {
                    $this->includeComponentTemplate($this->checkTemplate());
                }

                $this->putDataToCache();
            }

            if (!$this->cacheTemplate)
            {
                $this->includeComponentTemplate($this->checkTemplate());
            }

            if($this->arResult["ID"])
            {
                CIBlockElement::CounterInc($this->arResult["ID"]);
            }
            $this->setLastModified();

            if($this->isAjax()) { die(); }

            return $this->executeEpilog();
        }
        catch (Exception $e)
        {
            $this->abortDataCache();
            ShowError($e->getMessage());
        }
    }

    /**
     * Устанавливает Last-Modified для страницы
     */
    private function setLastModified()
    {
        if (array_key_exists(self::RESULT_LAST_MODIFIED, $this->arResult)) {
            \Mega\Main\Helper\Page::setLastModified($this->arResult[self::RESULT_LAST_MODIFIED]);
        }
    }

    public function checkTemplate()
    {
        if ($this->isAjax())
            return 'ajax';
    }
}
