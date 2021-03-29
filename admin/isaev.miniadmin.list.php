<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Grid\Options;
use \Bitrix\Main\UI\PageNavigation;
use \Bitrix\Main\Context;
use \Bitrix\Main\Loader;
use \Bitrix\Main\ORM\Query;
use \Bitrix\Catalog;
use \Bitrix\Main\Entity;
use \Bitrix\Main\UI;
use Isaev\Miniadmin;

/* BACKEND */
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

Loader::includeModule('isaev.miniadmin');
Loader::includeModule('iblock');
Loader::includeModule("catalog");

$context = \Bitrix\Main\Context::getCurrent();
$request = $context->getRequest();

$gridName = 'product_list';

// Обработка фильтра
$filter = [];
$filterOption = new UI\Filter\Options($gridName);

// Получаем все разделы магазина
$arFilterSection = Miniadmin\Helper::getCatalogSections(false, true);
$arPresetFilter = [];

// Доступные фильтры для использования
$arPresetFilter[] = [
    'id'     => 'sectionId',
    'name'   => 'Раздел',
    'type'   => 'list',
    'items'  => array_combine(array_column($arFilterSection, 'ID'), array_column($arFilterSection, 'NAME')),
    'params' => ['multiple' => 'N']
];

// Устанавливаем фильтр и отлавливаем через url
$filterOption->setCurrentPreset('tmp_filter');
$filterData = $filterOption->getFilter($arPresetFilter);

// Работа фильтра
foreach ($filterData as $filterName => $filterValue) {
    if (in_array($filterName, ['PRESET_ID', 'FILTER_ID', 'FILTER_APPLIED', 'FIND'])) {
        continue;
    }
    if ($filterName == 'sectionId') {
        $filter['sectionId'] = $filterValue;
    }
}

// Строка поиска
if (!empty($filterData['FIND'])) {
    // Общий поиск по полю 'name'
    $filter['name'] = '%'.$filterData['FIND'].'%';
}

// Навигация
$gridOption = new \Bitrix\Main\Grid\Options($gridName);
$sort = $gridOption->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
$navParam = $gridOption->GetNavParams();

// Инициализация пагинации
$nav = new PageNavigation($gridName);
$nav->allowAllRecords(true)->setPageSize($navParam['nPageSize'])->initFromUri();

// Поля для таблицы (Название столбцов)
$columnTable = Miniadmin\Helper::getColumnParams();

// Получем нужные нам элементы
$resElements = Catalog\ProductTable::getList(
    array_merge(
        [
            'filter' => $filter,
            'offset' => $nav->getOffset(),
            'limit'  => $nav->getLimit(),
            'order'  => $sort['sort'],
        ],
        Miniadmin\Helper::getParamsProductTable()
    )
);

// Получаем разделы этого уровня
$arSections = ($nav->getOffset() == 0 ? Miniadmin\Helper::getCatalogSections($filter['sectionId']) : []);

// Товары
$arProducts = $resElements->fetchAll();

// Кол-во всего
$countTotal = $resElements->getCount();
$nav->setRecordCount($countTotal);

// Колонки таблицы
foreach ($columnTable as $id => $column) {
    $arColumn[]         = ['id' => $id, 'name' => $column['name'], 'sort' => $id, 'default' => true];
    $arColumnKeys[$id]  = $id;
}

// Значения элементов
$arRows = [];
$countSection = 0;

// Разделы для грида
foreach ($arSections as $key => $section) {
    foreach ($arColumnKeys as $id) {
        if ($id == 'name') {
            $linkCategory = '<span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.$section['NAME'].'</span>';
            $arRows[$countSection]['data'][$id] = '<a href="'.Miniadmin\Helper::getLinkList($section['ID']).'" class="adm-list-table-icon-link">'.$linkCategory.'</a>';
        } elseif ($id == 'active') {
            $arRows[$countSection]['data'][$id] = ($section['ACTIVE'] == 'Y' ? 'Да' : 'Нет');
        }
    }
    $countSection++;
}


// Товары для грида
foreach ($arProducts as $key => $row) {
    $keyRow = $key + $countSection;
    foreach ($arColumnKeys as $id) {
        if ($id == 'name') {
            $arRows[$keyRow]['data'][$id] = '<a href="'.Miniadmin\Helper::getLinkEdit($row['elementId']).'">'.$row[$id].'</a>';
        } elseif ($id == 'active') {
            $arRows[$keyRow]['data'][$id] = ($row[$id] == 'Y' ? 'Да' : 'Нет');
        } else {
            $arRows[$keyRow]['data'][$id] = $row[$id];
        }
        $arRows[$keyRow]['data']['editable'] = true;
    }

    // Кнопки действий с элементами
    $arRows[$keyRow]['actions'] = [
        [
            'ICONCLASS' => 'menu-popup-item-edit',
            'text'      => 'Изменить',
            'onclick'   => 'document.location.href="'.Miniadmin\Helper::getLinkEdit($row['elementId']).'"',
            'default'   => true
        ],
    ];
}

/////////////////////////////////////////////////////////

/**
 * FRONTEND
 * Вывод визуальной части списка
 */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

$APPLICATION->IncludeComponent(
    'bitrix:main.ui.filter',
    '',
    [
        'FILTER_ID'          => $gridName,
        'GRID_ID'            => $gridName,
        'FILTER'             => $arPresetFilter,
        'ENABLE_LIVE_SEARCH' => true,
        'ENABLE_LABEL'       => false,
        'COMPACT_STATE'      => true
    ]
);

$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    [
        'GRID_ID'                   => $gridName,
        'COLUMNS'                   => $arColumn,
        'ROWS'                      => $arRows,
        'NAV_OBJECT'                => $nav,
        'AJAX_ID'                   => \CAjax::getComponentID('bitrix:main.ui.grid', '', ''),
        'TOTAL_ROWS_COUNT'          => $countTotal,
        'AJAX_MODE'                 => false,
        'SHOW_ROW_CHECKBOXES'       => false,
        'AJAX_OPTION_JUMP'          => false,
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_ROW_ACTIONS_MENU'     => true,
        'SHOW_GRID_SETTINGS_MENU'   => true,
        'SHOW_NAVIGATION_PANEL'     => true,
        'SHOW_PAGINATION'           => true,
        'SHOW_SELECTED_COUNTER'     => true,
        'SHOW_TOTAL_COUNTER'        => true,
        'SHOW_PAGESIZE'             => true,
        'SHOW_ACTION_PANEL'         => false,
        'ALLOW_COLUMNS_SORT'        => true,
        'ALLOW_COLUMNS_RESIZE'      => true,
        'ALLOW_HORIZONTAL_SCROLL'   => true,
        'ALLOW_SORT'                => true,
        'ALLOW_PIN_HEADER'          => true,
        'AJAX_OPTION_HISTORY'       => false,

        'PAGE_SIZES' => [
            ['NAME' => '20',  'VALUE' => '20'],
            ['NAME' => '50',  'VALUE' => '50'],
            ['NAME' => '100', 'VALUE' => '100'],
            ['NAME' => '500', 'VALUE' => '500']
        ],
    ]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
