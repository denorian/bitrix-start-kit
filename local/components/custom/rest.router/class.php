<?php

use Bitrix\Main\Loader;

class RestRouterComponent extends CBitrixComponent
{
    protected static $statusCodes = array(
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        404 => '404 Not Found',
        403 => '403 Forbidden',
        406 => '406 Not Acceptable',
        500 => '500 Internal Server Error'
    );

    public function onPrepareComponentParams($arParams)
    {
        $arParams['URL_PATHS'] = array_flip($arParams['SEF_URL_PATHS']);

        return parent::onPrepareComponentParams($arParams);
    }

    public function executeComponent()
    {
        $this->includeModules();

        try {
            $requestedMethod = \CComponentEngine::ParseComponentPath(
                $this->arParams['SEF_FOLDER'],
                $this->arParams['URL_PATHS']
            );

            $explodedMethod = explode('::', $requestedMethod);
            $class = "\Custom\Rest\\" . $explodedMethod[0];
            $method = str_replace('()', '', $explodedMethod[1]);

            if (method_exists($class, $method)) {
                $this->arResult = $class::$method();
            } else {
                CHTTP::SetStatus('404 Not Found');
                throw new \Bitrix\Main\SystemException('Method not found', 404);
            }
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            if (!array_key_exists($code, self::$statusCodes)) {
                CHTTP::SetStatus(self::$statusCodes[$code]);
            }
            echo $e->getMessage();
            return null;
        }

        if ($this->arResult['httpStatusCode'] > 1) {
            CHTTP::SetStatus(self::$statusCodes[$this->arResult['httpStatusCode']]);
            $this->arResult['status-code'] = self::$statusCodes[$this->arResult['httpStatusCode']];
            unset($this->arResult['httpStatusCode']);
        }

        header('Content-Type:application/json');
        header('Access-Control-Allow-Origin: *');
        if ($this->arResult || !isset($urlVariables['ID'])) {
            echo json_encode($this->arResult);
        } elseif (isset($urlVariables['ID'])) {
            CHTTP::SetStatus('404 Not Found');
        }

        return null;
    }

    /**
     * Подключение модулей
     */
    private function includeModules()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('custom.main');
        Loader::includeModule('custom.rest');
    }
}