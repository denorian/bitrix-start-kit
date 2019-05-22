<?php
namespace Custom\Main\Helpers;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectNotFoundException;

class Highloadblock
{
    private static $data;

    private $name;
    private $hlblock;
    private $arHLFields = array();

    /**
     * @param $name
     *
     * @return Highloadblock
     */
    public static function getInstance($name)
    {
        /** @var Highloadblock $entity */
        foreach (self::$data as $entity) {
            if (
                strcasecmp($entity->getName(), $name) == 0
                || is_numeric($name) && (int)$name == $entity->getId()
            ) {
                return $entity;
            }
        }

        $entity = new self($name);
        $name = $entity->getName();
        self::$data[$name] = $entity;

        return self::$data[$name];
    }

    private function __construct($name)
    {
        if (Loader::includeModule('highloadblock'))
        {
            $filter = [];
            if (is_numeric($name)) {
                $filter['ID'] = $name;
            } else {
                $filter['=NAME'] = $name;
            }
            $this->hlblock = HighloadBlockTable::getList(array(
                'filter' => $filter
            ))->fetch();
        }

        if (empty($this->hlblock)) {
            throw new ObjectNotFoundException(sprintf('Не найден HL-блок %s', $name));
        }

        $this->name = $this->hlblock['NAME'];
        $this->loadFieldsData();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->hlblock['ID'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return (string)$this->hlblock['NAME'];
    }

    /**
     * Загружает данные о полях HL-блока
     * @return void
     */
    private function loadFieldsData()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbResult = \CUserTypeEntity::GetList(
            array(),
            array(
                'ENTITY_ID' => 'HLBLOCK_' . $this->hlblock['ID'],
                'LANG' => 'ru'
            )
        );
        $arEnumFields = array();
        while ($arField = $dbResult->fetch()) {
            $this->arHLFields[$arField['FIELD_NAME']] = array(
                'ID' => $arField['ID'],
                'TYPE_ID' => $arField['USER_TYPE_ID'],
                'DEFAULT_VALUE' => isset($arField['SETTINGS'], $arField['SETTINGS']['DEFAULT_VALUE'])
                    ? $arField['SETTINGS']['DEFAULT_VALUE']
                    : false,
                'EDIT_FORM_LABEL' => $arField['EDIT_FORM_LABEL'],
                'HELP_MESSAGE' => $arField['HELP_MESSAGE'],
                'SETTINGS' => $arField['SETTINGS']
            );
            if ($arField['USER_TYPE_ID'] == 'enumeration')
            {
                $arEnumFields[$arField['ID']] = $arField['FIELD_NAME'];
            }
        }

        if (!empty($arEnumFields)) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $dbResult = \CUserFieldEnum::GetList(
                array(),
                array(
                    'USER_FIELD_ID' => array_keys($arEnumFields)
                )
            );
            while ($arValue = $dbResult->fetch()) {
                $this->arHLFields[$arEnumFields[$arValue['USER_FIELD_ID']]]['ENUM_XML_IDS'][$arValue['XML_ID']] = $arValue['ID'];
                $this->arHLFields[$arEnumFields[$arValue['USER_FIELD_ID']]]['ENUM_VALUES'][$arValue['XML_ID']] = $arValue['VALUE'];
            }
        }
    }

    /**
     * @return \Bitrix\Main\Entity\DataManager
     */
    public function getDataClass()
    {
        return HighloadBlockTable::compileEntity($this->hlblock)->getDataClass();
    }

    /**
     * Возвращает информацию о поле по его коду
     *
     * @param $code
     * @return bool|array
     */
    public function getField($code)
    {
        if (isset($this->arHLFields[$code])) {
            return $this->arHLFields[$code];
        }

        return false;
    }

    /**
     * Возвращает label поля по символьному коду
     *
     * @param string $code симвльный код поля
     * @return string
     */
    public function getFieldLabel($code)
    {
        if (isset(
            $this->arHLFields[$code],
            $this->arHLFields[$code]['EDIT_FORM_LABEL']
        )) {
            return $this->arHLFields[$code]['EDIT_FORM_LABEL'];
        }

        return '';
    }

    /**
     * Возвращает массив enum-значений поля по его коду
     *
     * @param string $code код поля
     * @return array|bool
     */
    public function getFieldEnumValues($code)
    {
        if (isset(
            $this->arHLFields[$code],
            $this->arHLFields[$code]['ENUM_VALUES']
        )) {
            return $this->arHLFields[$code]['ENUM_VALUES'];
        }

        return false;
    }

    /**
     * Возвращает массив enum-значений поля по его коду
     *
     * @param string $code код поля
     * @param $id
     * @return string|bool
     */
    public function getFieldEnumValue($code, $id)
    {
        if (
            ($xmlId = $this->getFieldEnumXmlId($code, $id))
            && isset(
                $this->arHLFields[$code],
                $this->arHLFields[$code]['ENUM_VALUES'],
                $this->arHLFields[$code]['ENUM_VALUES'][$xmlId]
            )
        ) {
            return $this->arHLFields[$code]['ENUM_VALUES'][$xmlId];
        }

        return false;
    }

    /**
     * Возвращает ID enum значения поля по его коду и XML_ID
     *
     * @param $code
     * @param $xmlId
     * @return int|bool
     */
        public function getFieldEnumId($code, $xmlId)
    {
        if (isset(
            $this->arHLFields[$code],
            $this->arHLFields[$code]['ENUM_XML_IDS'],
            $this->arHLFields[$code]['ENUM_XML_IDS'][$xmlId]
        )) {
            return (int)$this->arHLFields[$code]['ENUM_XML_IDS'][$xmlId];
        }

        return false;
    }

    /**
     * Возвращает XML_ID enum значения поля по его коду и ID
     *
     * @param $code
     * @param $id
     * @return string|bool
     */
    public function getFieldEnumXmlId($code, $id)
    {
        if (isset(
            $this->arHLFields[$code],
            $this->arHLFields[$code]['ENUM_XML_IDS']
        )) {
            return array_search($id, $this->arHLFields[$code]['ENUM_XML_IDS']);
        }

        return false;
    }
}