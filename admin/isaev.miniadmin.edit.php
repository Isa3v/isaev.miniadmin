<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Context;
use \Bitrix\Main\Loader;
use \Isaev\Miniadmin;
use \Bitrix\Main\ORM\Query;
use \Bitrix\Catalog;
use \Bitrix\Main\Entity;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

Loc::loadMessages(__FILE__);
Loader::includeModule('isaev.miniadmin');
Loader::includeModule('iblock');
Loader::includeModule("catalog");

$listUrl      = Miniadmin\Helper::getLinkList();
$context      = \Bitrix\Main\Context::getCurrent();
$request      = $context->getRequest();
$server       = $context->getServer();
$elementId    = (int) $request->get('id');
$arResult     = [];
$errorMessage = '';

// Поля для таблицы (Название столбцов)
$columnTable = Miniadmin\Helper::getColumnParams();

// POST ACTION
// Обновляем цену
if ($request->getPost('save') || $request->getPost('apply')) {
    if ($request->getPost('price')) {
        Miniadmin\Helper::updatePrice($request->getPost('productId'), $request->getPost('price'));
    }

    // Обновляем кол-во
    if ($request->getPost('qty')) {
        Catalog\ProductTable::update($request->getPost('productId'), ['QUANTITY' => $request->getPost('qty')]);
    }

    $element = new \CIBlockElement;

    // Отслеживаем checkbox активности
    if($request->getPost('active')) {
        $element->Update($request->getPost('productId'), ['ACTIVE' => $request->getPost('active')]);
    }else{
        $element->Update($request->getPost('productId'), ['ACTIVE' => 'N']);
    }

    if($request->getPost('save')) {
        localRedirect($listUrl);
    }else{
        localRedirect(Miniadmin\Helper::getLinkEdit($elementId));
    }
}

/**
 * Получаем данные если пришел ID
 */
if ($elementId > 0) {
    $filter = ['=productId' => $elementId];
    // Получем нужные нам элементы
    $arResult = Catalog\ProductTable::getList(
        array_merge(
            [
            'filter' => $filter
        ],
            Miniadmin\Helper::getParamsProductTable()
        )
    )->fetch();
    
    if(empty($arResult)) {
        localRedirect($listUrl);
    }

} else {
    localRedirect($listUrl);
}

// FRONTEND
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$APPLICATION->SetTitle('Товары: '.$arResult['name'].' - Редактирование');

$aTabs[] = [
    'DIV'   => 'edit1',
    'TAB'   => 'Товар',
    'ICON'  => 'sale',
    'TITLE' => 'Редактирование',
];

$tabControl = new \CAdminForm("tabControl", $aTabs);
$contextMenu = new \CAdminContextMenu([
    [
        'TEXT' => 'Товары',
        'LINK' => $listUrl,
        'ICON' => 'btn_list'
    ]
]);
$contextMenu->Show();

if (!empty($errorMessage)) {
    foreach ($errorMessage as $error) {
        \CAdminMessage::ShowMessage([
            "DETAILS" => $error,
            "TYPE"    => "ERROR",
            "MESSAGE" => 'Ошибка',
            "HTML"    => true
        ]);
    }
}
?>

<?$tabControl->BeginEpilogContent();?>
<?=bitrix_sessid_post();?>
<input type="hidden" name="Update" value="Y">
<input type="hidden" name="productId" value="<?=$elementId;?>" id="productId">

<?php
$tabControl->EndEpilogContent();
$tabControl->Begin(["FORM_ACTION" => Miniadmin\Helper::getLinkEdit($elementId)]);
$tabControl->BeginNextFormTab();
?>

<?php
// формирвем input
foreach ($columnTable as $code => $column) {
    $value = $request->get($code) ? $request->get($code) : $arResult[$code];
    if ($code == 'elementId' || $code == 'name') {
        $tabControl->AddViewField($code, $column['name'].':', $value, true);
    } elseif ($code == 'active') {
        $tabControl->AddCheckBoxField($code, $column['name'].':', false, 'Y', ($value == 'Y' ?: false));
    } else {
        $tabControl->AddEditField($code, $column['name'].':', true, [], $value);
    }
}
$tabControl->Buttons(["back_url" => $listUrl]);
$tabControl->Show();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
