<?
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @global string $mid */

use Bitrix\Main\Loader;

$moduleId = "itl.alstyle";

Loader::includeModule($moduleId);

$ITL_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if ($ITL_RIGHT >= "R") {
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $moduleId . "/options.php");

	$arAllOptions = array(
		array("ITL_ALSTYLE_ACCESS_TOKEN", GetMessage("ITL_ALSTYLE_TOKEN"), array("text", 30)),
	);

	$aTabs = array(
		array("DIV" => "alstyle_settings", "TAB" => GetMessage("ITL_ALSTYLE_TAB_SETTINGS"), "TITLE" => GetMessage("ITL_ALSTYLE_TAB_SETTINGS")),
	);

	$tabControl = new CAdminTabControl("tabControl", $aTabs);

	if ($_SERVER['REQUEST_METHOD'] == "POST" && $Update.$Apply.$RestoreDefaults <> '' && $ITL_RIGHT >= "W" && check_bitrix_sessid()) {
		foreach ($arAllOptions as $arOption) {
			$name = $arOption[0];
			$val = $_REQUEST[$name];
			if ($arOption[2][0] == "checkbox" && $val != "Y")
				$val = "N";
			COption::SetOptionString($moduleId, $name, $val, $arOption[1]);
		}

		if ($_REQUEST["back_url_settings"] <> '')
		{
			if (($Apply <> '') || ($RestoreDefaults <> ''))
				LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($moduleId)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
			else
				LocalRedirect($_REQUEST["back_url_settings"]);
		}
		else
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($moduleId)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
		}
	}
	?>

	<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>"><?=bitrix_sessid_post()?>
		<?
		$tabControl->Begin();
		$tabControl->BeginNextTab();
		?>
		<? foreach ($arAllOptions as $arOption) {?>
			<?
			$val = COption::GetOptionString($moduleId, $arOption[0]);
			$type = $arOption[2];
			if (isset($arOption[3])) {
				$arNotes[] = $arOption[3];
			}
			?>
			<tr>
				<td width="40%" nowrap <? if ($type[0] == "textarea")
					echo 'class="adm-detail-valign-top"' ?>>
					<? if (isset($arOption[3])): ?>
						<span class="required"><sup><? echo count($arNotes) ?></sup></span>
					<? endif; ?>
					<label for="<? echo htmlspecialcharsbx($arOption[0]) ?>"><? echo $arOption[1] ?>:</label>
				</td>
				<td width="60%">
					<? if ($type[0] == "checkbox"): ?>
						<input
							type="checkbox"
							name="<? echo htmlspecialcharsbx($arOption[0]) ?>"
							id="<? echo htmlspecialcharsbx($arOption[0]) ?>"
							value="Y"<? if ($val == "Y") echo " checked"; ?>>
					<? elseif ($type[0] == "text"): ?>
						<input
							type="text"
							size="<? echo $type[1] ?>"
							maxlength="255"
							value="<? echo htmlspecialcharsbx($val) ?>"
							name="<? echo htmlspecialcharsbx($arOption[0]) ?>"
							id="<? echo htmlspecialcharsbx($arOption[0]) ?>">
						<? if ($arOption[0] == "slow_sql_time")
							echo GetMessage("PERFMON_OPTIONS_SLOW_SQL_TIME_SEC") ?>
						<? if ($arOption[0] == "large_cache_size")
							echo GetMessage("PERFMON_OPTIONS_LARGE_CACHE_SIZE_KB") ?>
					<?
					elseif ($type[0] == "textarea"): ?>
						<textarea
							rows="<? echo $type[1] ?>"
							cols="<? echo $type[2] ?>"
							name="<? echo htmlspecialcharsbx($arOption[0]) ?>"
							id="<? echo htmlspecialcharsbx($arOption[0]) ?>"
						><? echo htmlspecialcharsbx($val) ?></textarea>
					<?endif ?>
				</td>
			</tr>
		<? } ?>

		<?$tabControl->Buttons(); ?>
		<input <? if ($ITL_RIGHT < "W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("ITL_ALSTYLE_SAVE")?>" title="<?=GetMessage("ITL_ALSTYLE_SAVE_TITLE")?>" class="adm-btn-save">
		<input <? if ($ITL_RIGHT < "W") echo "disabled" ?> type="submit" name="Apply" value="<?=GetMessage("ITL_ALSTYLE_APPLY")?>" title="<?=GetMessage("ITL_ALSTYLE_APPLY_TITLE")?>">
		<? if ($_REQUEST["back_url_settings"] <> ''): ?>
			<input
				<? if ($ITL_RIGHT < "W") echo "disabled" ?>
				type="button" name="Cancel"
				value="<?=GetMessage("MAIN_OPT_CANCEL")?>"
				title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>"
				onclick="window.location='<? echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"])) ?>'"
			>
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
		<? endif ?>
		<input type="submit" name="RestoreDefaults"
			title="<? echo GetMessage("ITL_ALSTYLE_RESTORE_DEFAULTS_TITLE") ?>"
			onclick="return confirm('<? echo AddSlashes(GetMessage("ITL_ALSTYLE_RESTORE_DEFAULTS_WARNING")) ?>')"
			value="<? echo GetMessage("ITL_ALSTYLE_RESTORE_DEFAULTS") ?>"
		>
		<?=bitrix_sessid_post();?>
		<? $tabControl->End(); ?>
    </form>
<? } ?>
