<?
//<title>Al-Style</title>
use Bitrix\Main,
	Bitrix\Catalog;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/catalog/import_setup_templ.php');
//IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/itl.alstyle/import_alstyle_setup.php');

/** @global string $ACTION */
/** @global int $IBLOCK_ID */

global $APPLICATION, $USER;

$arSetupErrors = array();

//********************  ACTIONS  **************************************//
if (($ACTION == 'IMPORT_EDIT' || $ACTION == 'IMPORT_COPY') && $STEP == 1) {
	if (isset($arOldSetupVars['IBLOCK_ID']))
		$IBLOCK_ID = $arOldSetupVars['IBLOCK_ID'];
	if (isset($arOldSetupVars['SITE_ID']))
		$SITE_ID = $arOldSetupVars['SITE_ID'];
}

if ($STEP > 1) {
	if (empty($arSetupErrors)) {
		$IBLOCK_ID = (int)$IBLOCK_ID;
		$arIBlock = array();
		if ($IBLOCK_ID <= 0) {
			$arSetupErrors[] = GetMessage("CATI_NO_IBLOCK");
		} else {
			$arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);
			if (false === $arIBlock) {
				$arSetupErrors[] = GetMessage("CATI_NO_IBLOCK");
			}
		}
	}

	if (empty($arSetupErrors)) {
		if (!CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, 'iblock_admin_display'))
			$arSetupErrors[] = GetMessage("CATI_NO_IBLOCK_RIGHTS");
	}

	if (!empty($arSetupErrors)) {
		$STEP = 1;
	}
}
//********************  END ACTIONS  **********************************//

$aMenu = array(
	array(
		"TEXT" => GetMessage("CATI_ADM_RETURN_TO_LIST"),
		"TITLE" => GetMessage("CATI_ADM_RETURN_TO_LIST_TITLE"),
		"LINK" => "/bitrix/admin/cat_import_setup.php?lang=" . LANGUAGE_ID,
		"ICON" => "btn_list",
	)
);

$context = new CAdminContextMenu($aMenu);

$context->Show();

if (!empty($arSetupErrors))
	ShowError(implode('<br>', $arSetupErrors));

$actionParams = "";
if ($adminSidePanelHelper->isSidePanel()) {
	$actionParams = "?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER";
}
?>
<!--suppress JSUnresolvedVariable -->
<form method="POST" action="<? echo $APPLICATION->GetCurPage() . $actionParams; ?>" ENCTYPE="multipart/form-data" name="dataload">
	<?
	$aTabs = array(
		//array("DIV" => "edit1", "TAB" => GetMessage("ITL_ALSTYLE_EXP_TAB1"), "ICON" => "store", "TITLE" => GetMessage("ITL_ALSTYLE_EXP_TAB1_TITLE")),
		//array("DIV" => "edit2", "TAB" => GetMessage("ITL_ALSTYLE_EXP_TAB2"), "ICON" => "store", "TITLE" => GetMessage("ITL_ALSTYLE_EXP_TAB2_TITLE")),
		array("DIV" => "edit1", "TAB" => GetMessage("CAT_ADM_CSV_IMP_TAB1"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CSV_IMP_TAB1_TITLE")),
		array("DIV" => "edit2", "TAB" => GetMessage("CAT_ADM_CSV_IMP_TAB4"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CSV_IMP_TAB4_TITLE")),

		/*array("DIV" => "edit2", "TAB" => GetMessage("CAT_ADM_CSV_IMP_TAB2"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CSV_IMP_TAB2_TITLE")),
		array("DIV" => "edit3", "TAB" => GetMessage("CAT_ADM_CSV_IMP_TAB3"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CSV_IMP_TAB3_TITLE")),
		array("DIV" => "edit4", "TAB" => GetMessage("CAT_ADM_CSV_IMP_TAB4"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CSV_IMP_TAB4_TITLE")),*/
	);

	$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
	$tabControl->Begin();

	$tabControl->BeginNextTab();

	if ($STEP == 1) {
		?>
		<tr class="heading">
			<td colspan="2"><? echo GetMessage("CATI_DATA_LOADING"); ?></td>
		</tr>
		<tr>
			<td valign="top" width="40%"><? echo GetMessage("CATI_INFOBLOCK"); ?>:</td>
			<td valign="top" width="60%"><?
				if (!isset($IBLOCK_ID))
					$IBLOCK_ID = 0;
				echo GetIBlockDropDownListEx(
					$IBLOCK_ID,
					'IBLOCK_TYPE_ID',
					'IBLOCK_ID',
					array('CHECK_PERMISSIONS' => 'Y', 'MIN_PERMISSION' => 'W'),
					"",
					"",
					'class="adm-detail-iblock-types"',
					'class="adm-detail-iblock-list"'
				);
				?>
			</td>
		</tr>
		<?
	}

	$tabControl->EndTab();

	$tabControl->BeginNextTab();

	if ($STEP == 2) {
		$FINITE = true;
	}

	$tabControl->EndTab();

	$tabControl->Buttons();

	?><? echo bitrix_sessid_post(); ?><?

	if ($ACTION == 'IMPORT_EDIT' || $ACTION == 'IMPORT_COPY') {
		?><input type="hidden" name="PROFILE_ID" value="<? echo intval($PROFILE_ID); ?>"><?
	}

	if ($STEP < 2) {
		?><input type="hidden" name="STEP" value="<? echo intval($STEP) + 1; ?>">
		<input type="hidden" name="lang" value="<? echo LANGUAGE_ID; ?>">
		<input type="hidden" name="ACT_FILE" value="<? echo htmlspecialcharsbx($_REQUEST["ACT_FILE"]); ?>">
		<input type="hidden" name="ACTION" value="<? echo htmlspecialcharsbx($ACTION); ?>">
		<?
		if ($STEP > 1) {
			?><input type="hidden" name="IBLOCK_ID" value="<? echo intval($IBLOCK_ID); ?>">
			<?
		}

		if ($STEP > 1) {
			?>
			<input type="submit" name="backButton" value="&lt;&lt; <? echo GetMessage("CATI_BACK") ?>">
		<?}?>
		<input type="submit" value="<? echo (($ACTION=="IMPORT")?GetMessage("CICML_NEXT_STEP_F"):GetMessage("CICML_SAVE"))." &gt;&gt;" ?>" name="submit_btn">
		<?
	}

	$tabControl->End();
	?>
</form>
<script type="text/javascript">
	<?if ($STEP < 2):?>
		tabControl.SelectTab("edit1");
		tabControl.DisableTab("edit2");
	<?elseif ($STEP == 2):?>
		tabControl.SelectTab("edit2");
		tabControl.DisableTab("edit1");
	<?endif;?>

	function showTranslitSettings() {
		var useTranslit = BX('USE_TRANSLIT_Y'),
			translitLang = BX('tr_TRANSLIT_LANG'),
			translitUpdate = BX('tr_USE_UPDATE_TRANSLIT');
		if (!BX.type.isElementNode(useTranslit) || !BX.type.isElementNode(translitLang) || !BX.type.isElementNode(translitUpdate))
			return;
		BX.style(translitLang, 'display', (useTranslit.checked ? 'table-row' : 'none'));
		BX.style(translitUpdate, 'display', (useTranslit.checked ? 'table-row' : 'none'));
	}

	BX.ready(function () {
		var useTranslit = BX('USE_TRANSLIT_Y'),
			translitLang = BX('tr_TRANSLIT_LANG'),
			translitUpdate = BX('tr_USE_UPDATE_TRANSLIT');
		if (BX.type.isElementNode(useTranslit) && BX.type.isElementNode(translitLang) && BX.type.isElementNode(translitUpdate))
			BX.bind(useTranslit, 'click', showTranslitSettings);
	});
</script>