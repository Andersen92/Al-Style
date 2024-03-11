<?php
IncludeModuleLangFile(__FILE__);

if (class_exists("itl_alstyle")) return;

use Bitrix\Main\Localization\Loc;
use	Bitrix\Main\ModuleManager;

class itl_alstyle extends CModule
{
	const MODULE_ID = 'itl.alstyle';

	var $MODULE_ID = "itl.alstyle";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path . "/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		} else {
			$this->MODULE_VERSION = ITL_ALSTYLE_VERSION;
			$this->MODULE_VERSION_DATE = ITL_ALSTYLE_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("ITL_ALSTYLE_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("ITL_ALSTYLE_MODULE_DESCRIPTION");
		$this->PARTNER_NAME = GetMessage("ITL_ALSTYLE_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("ITL_ALSTYLE_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		$mPath = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/";

		CopyDirFiles($mPath . $this->MODULE_ID . "/install/catalog_import/alstyle_run.php", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/include/catalog_import/alstyle_run.php");
		CopyDirFiles($mPath . $this->MODULE_ID . "/install/catalog_import/alstyle_setup.php", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/include/catalog_import/alstyle_setup.php");

		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFilesEx("/bitrix/php_interface/include/catalog_import/alstyle_run.php");
		DeleteDirFilesEx("/bitrix/php_interface/include/catalog_import/alstyle_setup.php");

		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $errors;

		$errors = false;

		if(!ModuleManager::isModuleInstalled("currency")) {
			$errors = Loc::getMessage("ITL_ALSTYLE_UNINS_CURRENCY");
		} if (!ModuleManager::isModuleInstalled("iblock")) {
			$errors = Loc::getMessage("ITL_ALSTYLE_UNINS_IBLOCK");
		} elseif (!ModuleManager::isModuleInstalled("catalog")) {
			$errors = Loc::getMessage("ITL_ALSTYLE_UNINS_CATALOG");
		} else {
			$this->InstallFiles();
			$this->InstallDB();
			RegisterModule(self::MODULE_ID);
		}

		$APPLICATION->IncludeAdminFile(Loc::getMessage("ITL_ALSTYLE_INSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . self::MODULE_ID . "/install/step.php");
	}

	function DoUninstall()
	{
		global $APPLICATION;

		UnRegisterModule(self::MODULE_ID);
		$this->UnInstallDB();
		$this->UnInstallFiles();

		$APPLICATION->IncludeAdminFile(Loc::getMessage("ITL_ALSTYLE_UNINSTALL_TITLE"), $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/" . self::MODULE_ID . "/install/unstep.php");
	}
}
