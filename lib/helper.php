<?php
/**
 * @author Isaev Danil
 * @package Isaev\Miniadmin
 */

namespace Isaev\Miniadmin;

use \Bitrix\Main\ORM\Query;
use \Bitrix\Catalog;
use \Bitrix\Main\Entity;

class Helper
{
    /**
     * getLinkEdit
     *
     * @param  mixed $id
     * @return void
     */
    public function getLinkEdit(string $id)
    {
        $result = '/bitrix/admin/isaev.miniadmin.edit.php?id='.$id;
        return $result;
    }
    
    /**
     * getLinkList
     *
     * @param  mixed $id
     * @return void
     */
    public function getLinkList($id = null)
    {
        if (!empty($id)) {
            $result = '/bitrix/admin/isaev.miniadmin.list.php?sectionId='.$id.'&apply_filter=Y';
        } else {
            $result = '/bitrix/admin/isaev.miniadmin.list.php';
        }
        return $result;
    }
    
    /**
     * getParamsProductTable
     * Общие параметры datamanager для вызова товаров
     *
     * @return void
     */
    public function getParamsProductTable()
    {
        return [
            'select' => [
                'active'    => 'IBLOCK_ELEMENT.ACTIVE',
                'name'      => 'IBLOCK_ELEMENT.NAME',
                'elementId' => 'IBLOCK_ELEMENT.ID',
                'productId' => 'ID',
                'price'     => 'PRICES.PRICE',
                'qty'       => 'QUANTITY',
                'iblockId'  => 'IBLOCK_ELEMENT.IBLOCK_ID',
                'sectionId' => 'IBLOCK_ELEMENT.IBLOCK_SECTION_ID'
            ],
            'runtime' => [
                new Entity\ReferenceField(
                    'PRICES',
                    Catalog\PriceTable::class,
                    Query\Join::on('this.productId', 'ref.PRODUCT_ID')
                )
            ],
            'count_total' => true
        ];
    }
    
    /**
     * getColumnParams
     * Колонки для списка и детальной
     *
     * @return void
     */
    public function getColumnParams()
    {
        return [
            'elementId' => [
                'name' => 'ID',
                'sort' => 500,
            ],
            'name' => [
                'name' => 'Название',
                'sort' => 500,
            ],
            'active' => [
                'name' => 'Активность',
                'sort' => 500,
                'items' => ['Y' =>  'Да', 'N' =>  'Нет']
            ],
            'price' => [
                'name' => 'Цена',
                'sort' => 500,
            ],
            'qty' => [
                'name' => 'Доступное количество',
                'sort' => 500,
            ]
        ];
    }
    
    /**
     * updatePrice
     * Обновление цены
     *
     * @param  mixed $id
     * @param  mixed $price
     * @return void
     */
    public function updatePrice($id, $price)
    {
        $result = false;
        if (!empty($id) && !empty($price)) {
            $arPrice = Catalog\PriceTable::getList(['filter' => ['=PRODUCT_ID' => $id]])->fetch();
            
            if (!empty($arPrice)) {
                $arFieldsPrice = [
                    "PRODUCT_ID"       => $arPrice['PRODUCT_ID'],
                    "CATALOG_GROUP_ID" => $arPrice['CATALOG_GROUP_ID'],
                    "PRICE"            => $price,
                    "CURRENCY"         => $arPrice['CURRENCY'],
                ];
                
                $update = Catalog\Model\Price::update($arPrice["ID"], $arFieldsPrice);
                if ($update->isSuccess()) {
                    $result = true;
                }
            }
        }

        return $result;
    }
        
    /**
     * getCatalogSections
     * Получение разделов текущего уровня
     *
     * @param  mixed $sectionId
     * @return array
     */
    public function getCatalogSections($sectionId = false, $showAll = false)
    {
        $filter = [];
        $filter['!CatalogIblock.IBLOCK_ID'] = false;
        if ($showAll == false) {
            $filter['IBLOCK_SECTION_ID'] = $sectionId;
        };

        $rsSections = \Bitrix\Iblock\SectionTable::getList([
            'filter' => $filter,
            'select' => [
                'NAME',
                'ID',
                'SORT',
                'ACTIVE'
            ],
            'runtime' => [
                new Entity\ReferenceField(
                    'CatalogIblock',
                    \Bitrix\Catalog\CatalogIblockTable::class,
                    Query\Join::on('this.IBLOCK_ID', 'ref.IBLOCK_ID')
                )
            ],
        ]);

        $result = $rsSections->fetchAll();
        return $result;
    }
}
