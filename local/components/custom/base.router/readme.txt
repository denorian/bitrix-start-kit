Пример кода вызова базового компонента роутинга

<?$APPLICATION->IncludeComponent('custom:base.router', '', array(
    "CACHE_TYPE" => "A",
    "CACHE_TIME" => 3600,
    "SEF_FOLDER" => '/article/',
    "SEF_MODE" => "Y",
    "SEF_URL_TEMPLATES" => array(
        "detail" => "#ELEMENT_ID#/",
        "index" => "index.php",
    ),
));?>