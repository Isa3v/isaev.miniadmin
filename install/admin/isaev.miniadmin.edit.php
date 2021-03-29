<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php";
$path = \Bitrix\Main\Loader::getLocal('/modules/isaev.miniadmin/admin/isaev.miniadmin.edit.php');
require $path;
