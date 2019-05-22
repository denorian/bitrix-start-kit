<?
$module_id = "custom.main";
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $module_id . "/include.php");
IncludeModuleLangFile(__FILE__);
/**
 * Получаем права пользователя
 */
$ACCESS = $APPLICATION->GetGroupRight($module_id);
if ($ACCESS >= "R") :
    if ($REQUEST_METHOD == "GET" && $ACCESS == "W" && strlen($RestoreDefaults) > 0 && check_bitrix_sessid()) {
        COption::RemoveOption($module_id);
        $z = CGroup::GetList($v1 = "id", $v2 = "asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
        while ($zr = $z->Fetch())
            $APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
    }
    $arAllOptions = array
    (
        array("", GetMessage("PREVIEW_TITLE"), array("heading")),

    );

    $aTabs = array(
        array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "T_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);
    if ($REQUEST_METHOD == "POST" && strlen($Update . $Apply) > 0 && $ACCESS >= "W" && check_bitrix_sessid()) {
        foreach ($arAllOptions as $arOption) {
            $name = $arOption[0];
            $val = trim($_REQUEST[$name], " \t\n\r");
            $type = $arOption[2][0];
            if ($type === 'heading')
                continue;
            COption::SetOptionString($module_id, $name, $val);
        }

        if ($_REQUEST['DISABLE_INTERFACE_CUSTOMIZATION_GROUPS']) {
            COption::SetOptionString($module_id, 'DISABLE_INTERFACE_CUSTOMIZATION_GROUPS', serialize($_REQUEST['DISABLE_INTERFACE_CUSTOMIZATION_GROUPS']));
        }
        $Update = $Update . $Apply;
        ob_start();
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
        ob_end_clean();
        if ($Apply == '' && $_REQUEST["back_url_settings"] <> '')
            LocalRedirect($_REQUEST["back_url_settings"]);
        else
            LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($module_id) . "&lang=" . urlencode(LANGUAGE_ID) . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"]) . "&" . $tabControl->ActiveTabParam());

    }
    ?>
    <form method="post"
          action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&lang=<?= LANGUAGE_ID ?>" enctype="multipart/form-data">
        <?
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        for ($i = 0; $i < count($arAllOptions); $i++):
            $arOption = $arAllOptions[$i];
            $type = $arOption[2];
            ?>
            <? if ($type[0] == "heading"): ?>
            <tr class="heading">
                <td colspan="2"><b><?= $arOption[1] ?></b></td>
            </tr>
        <? else: ?>
            <? $val = COption::GetOptionString($module_id, $arOption[0]); ?>
            <tr>
                <td width="40%">
                    <label for="<?= htmlspecialcharsbx($arOption[0]) ?>"><?= $arOption[1] ?>:</label>
                </td>
                <td width="60%">
                    <? if ($type[0] == "checkbox"): ?>
                        <input type="checkbox" name="<? echo htmlspecialcharsbx($arOption[0]) ?>"
                               id="<? echo htmlspecialcharsbx($arOption[0]) ?>"
                               value="Y"<? if ($val == "Y") echo " checked"; ?>>
                    <? elseif ($type[0] == "text"): ?>
                        <input type="text" size="<? echo $type[1] ?>" maxlength="255"
                               value="<? echo htmlspecialcharsbx($val) ?>"
                               name="<? echo htmlspecialcharsbx($arOption[0]) ?>"
                               id="<? echo htmlspecialcharsbx($arOption[0]) ?>">
                    <? elseif ($type[0] == "textarea"): ?>
                        <textarea rows="<? echo $type[1] ?>" cols="<? echo $type[2] ?>"
                                  name="<? echo htmlspecialcharsbx($arOption[0]) ?>"
                                  id="<? echo htmlspecialcharsbx($arOption[0]) ?>"><? echo htmlspecialcharsbx($val) ?></textarea>
                    <? elseif ($type[0] == "selectbox"):
                        echo SelectBoxFromArray($arOption[0], $type[1], $val);
                    endif ?>
                </td>
            </tr>
        <? endif; ?>
        <? endfor; ?>
        <tr class="heading">
            <td colspan="2"><?=GetMessage('DISABLE_INTERFACE_CUSTOMIZATION_GROUPS_TITLE')?></td>
        </tr>
        <tr>
            <td><?= GetMessage("DISABLE_INTERFACE_CUSTOMIZATION_GROUPS");?></td>
            <td>
                <?php
                $valGroups = COption::GetOptionString($module_id, 'DISABLE_INTERFACE_CUSTOMIZATION_GROUPS');
                $valGroups = unserialize($valGroups);
                $rsGroups = CGroup::GetList($by = "c_sort", $order = "asc", Array());
                ?>
                <select name="DISABLE_INTERFACE_CUSTOMIZATION_GROUPS[]" multiple size="5">
                    <?
                    while ($arGroups = $rsGroups->Fetch()) {
                        $selected = in_array($arGroups['ID'], $valGroups) ? 'selected' : '';
                        ?>
                        <option value="<?= $arGroups['ID']; ?>" <?= $selected;?>><?= $arGroups['NAME']; ?></option>
                    <? } ?>
                </select>
            </td>
        </tr>

        <? $tabControl->BeginNextTab(); ?>
        <? require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php"); ?>
        <? $tabControl->Buttons(); ?>
        <? if (strlen($_REQUEST["back_url_settings"]) > 0): ?>
            <input type="submit" name="Update" value="<?= GetMessage("MAIN_SAVE") ?>"
                   title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>"<? if ($ACCESS < "W") echo " disabled" ?>>
        <? endif ?>
        <input type="submit" name="Apply" value="<?= GetMessage("MAIN_OPT_APPLY") ?>"
               title="<?= GetMessage("MAIN_OPT_APPLY_TITLE") ?>"<? if ($ACCESS < "W") echo " disabled" ?>>
        <? if (strlen($_REQUEST["back_url_settings"]) > 0): ?>
            <input type="button" name="Cancel" value="<?= GetMessage("MAIN_OPT_CANCEL") ?>"
                   title="<?= GetMessage("MAIN_OPT_CANCEL_TITLE") ?>"
                   onclick="window.location='<? echo htmlspecialcharsbx(CUtil::JSEscape($_REQUEST["back_url_settings"])) ?>'">
            <input type="hidden" name="back_url_settings"
                   value="<?= htmlspecialcharsbx($_REQUEST["back_url_settings"]) ?>">
        <? endif ?>
        <?= bitrix_sessid_post(); ?>
        <? $tabControl->End(); ?>
    </form>
<? endif; ?>