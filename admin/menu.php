<?php
/**
 * @author Isaev Danil
 * @package Isaev\Miniadmin
 * 
 * This file creates a tab with this module in the administrative menu
 * Даныый файл создает в административном меню вкладку с данным модулем
 */
use \Bitrix\Main\Localization\Loc;

if(\Bitrix\Main\ModuleManager::isModuleInstalled('isaev.miniadmin')){
    Loc::loadMessages(__FILE__);
    $aMenu = [
        [
            'parent_menu' => 'global_menu_store',
            'sort'        => 0,
            "icon"        => "bizproc_menu_icon",
            'text'        => 'Товары',
            'url'         => 'isaev.miniadmin.list.php',
            'dynamic'     => false,
            'items_id'    => 'menu_isaev_miniadmin',
            'module_id'   => 'isaev.miniadmin',
            'more_url'    => [
                'isaev.miniadmin.edit.php',
            ],
        ],
    ];
    return $aMenu;
}
return false;
