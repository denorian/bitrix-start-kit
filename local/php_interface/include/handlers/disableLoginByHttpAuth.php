<?php
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'main',
    'onBeforeUserLoginByHttpAuth',
    function (&$arAuth) {
    return false;
});