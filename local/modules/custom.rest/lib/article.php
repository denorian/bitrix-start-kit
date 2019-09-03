<?php

namespace Custom\Rest;

use Bitrix\Main\Context;

class Article extends Base
{
    public function getList()
    {
        return ['element' => '1'];
    }

    public function getDetail()
    {
        $id = Context::getCurrent()->getRequest()->get('id');

        return ['element' => '1'];
    }

    public function upsert(){
        $postID = Context::getCurrent()->getRequest()->getPost('id');

        return ['element' => '1'];
    }
}