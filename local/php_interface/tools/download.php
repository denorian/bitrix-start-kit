<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

//Скрипт для скачивания файла залитого в свойство ИБ с его первоначальным именем
use Bitrix\Main\Application;
use Bitrix\Main\FileTable;

$request = Application::getInstance()->getContext()->getRequest();
$fileID = intval($request->get("file_id"));
$key = $request->get("key");
if ($fileID > 0 && $key) {
    if (md5($fileID . '_secure') == $key) {
        $arFile = FileTable::getById($fileID)->fetch();
        // далее  от греха подальше нужно провести транслитерацию русских символов на английские
        $originalName = Cutil::translit($arFile["ORIGINAL_NAME"], "ru", ["replace_space" => "-", "replace_other" => "-"]);
        // опредеяем путь к файлу
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $arFile['SUBDIR'] . '/' . $arFile['FILE_NAME'];
        //проверяем, а есть ли вообще этот файл
        if (!file_exists($filePath)) {
            die("Error: file not found.");
        } else {
            if($arFile["CONTENT_TYPE"] == 'application/pdf'){
                header('Content-Disposition: inline; filename="' . $originalName . '"');
            }else{
                header("Content-Disposition: attachment; filename=" . $originalName);
            }
            header("Content-Type: " . $arFile["CONTENT_TYPE"]);
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            readfile($filePath);
        };
    } else {
        die("Error: invalid key");
    }
} else {
    die("Error: prop file_id or key not received");
};