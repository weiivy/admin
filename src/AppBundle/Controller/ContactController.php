<?php
namespace AppBundle\Controller;


use AppBundle\Service\ContactService;
use Rain\Pagination;
use Rain\Request;
use Rain\View;

class ContactController
{
    /**
     * 粉丝列表
     */
    public function getIndex(Request $request)
    {
        $condition = $params = [];
        $openid = $request->get('openid');
        if(!empty($openid)) {
            $condition[] = 'openid = :openid';
            $params[':openid'] = $openid;
        }

        $nickname = $request->get('nickname');
        if(!empty($nickname)) {
            $condition[] = "nickname like '%". trim($nickname) ."%'";
        }

        $p= $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        return View::render('@AppBundle/contact/list.twig', [
            'list' => ContactService::findContactList($page, $condition, $params),
            'page' => $page,
        ]);
    }
}