<?php
namespace AppBundle\Service;


use AppBundle\Entity\Contact;
use Rain\DB;
use Rain\Pagination;

class ContactService
{
    /**
     * 粉丝列表（带分页）
     * @param Pagination $page
     * @param array $condition
     * @param array $params
     * @param string $order
     * @return array
     */
    public static function findContactList(Pagination $page, $condition = [], $params = [], $order = 'id desc')
    {
        $query = DB::select(Contact::tableName())->where(implode(' and ', $condition), $params);
        $queryCount = clone $query;
        $page->itemCount = $queryCount->count();

        return $query->asEntity(Contact::className())
            ->limit($page->limit)
            ->orderBy($order)
            ->findAll();
    }
}