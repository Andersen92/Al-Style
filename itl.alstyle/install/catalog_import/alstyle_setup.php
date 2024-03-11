<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/catalog_export/alstyle_setup.php")){
    require($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/catalog_import/alstyle_setup.php");
}else{
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/itl.alstyle/load/alstyle_setup.php");
}
?>