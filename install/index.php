<?php
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;

class isaev_miniadmin extends CModule
{
    const MODULE_ID = "isaev.miniadmin";
    public $MODULE_ID = "isaev.miniadmin";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_VERSION      = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME         = "Мини-админка";
        $this->MODULE_DESCRIPTION  = "Свои вкладки в панели администратор";
        $this->PARTNER_NAME        = "Isaev";
        $this->PARTNER_URI         = "https://partners.1c-bitrix.ru/program/partners_list/10338950.php";
    }
    public function installEvents()
    {
        $eventManager = EventManager::getInstance();
        return true;
    }
    public function unInstallEvents()
    {
        $eventManager = EventManager::getInstance();

        return true;
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installEvents();
        $path = \Bitrix\Main\Loader::getLocal('/modules/isaev.miniadmin/install/admin');
        CopyDirFiles($path, Application::getDocumentRoot() . "/bitrix/admin", true, true);

        // Компонент установка
        $componentPath = \Bitrix\Main\Loader::getLocal('/modules/isaev.miniadmin/install/components');
        CopyDirFiles($componentPath, Application::getDocumentRoot()."/bitrix/components", true, true);
        return true;
    }
    
    public function doUninstall()
    {
        $this->unInstallEvents();
        $path = \Bitrix\Main\Loader::getLocal('/modules/isaev.miniadmin/install/admin');
        DeleteDirFiles($path, Application::getDocumentRoot() . "/bitrix/admin");
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }
}
