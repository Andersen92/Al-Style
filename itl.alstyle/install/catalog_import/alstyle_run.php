<?
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/catalog_export/alstyle_run.php")) {
	require($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/catalog_import/alstyle_run.php");
} else {
	require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/itl.alstyle/load/alstyle_run.php");
}
?>
