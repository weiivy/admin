<?php

namespace Rain;

/**
 * 分页类
 *
 * @property string limit 返回可用于MySQL LIMIT SQL语句
 * @property int offset
 * @property int itemCount 总记录数
 * @property int pageCount 总页数
 * @property int currentPage 当前页码
 * @property int pageSize 每页显示记录数
 *
 * @author  Zou Yiliang
 */
class Pagination
{
    protected $itemCount;         //总记录数
    protected $currentPage;       //当前页码
    protected $offset;            //数据库查询的偏移量(查询开始的记录)
    protected $pageSize;          //每页显示记录数
    protected $pageCount;         //总页数
    protected $prevPage;          //当前的上一页码
    protected $nextPage;          //当前的下一页码

    protected $url;               //访问当前action不带分页的url

    protected $pageVar = 'page';                   //分页get变量
    protected $className = 'pagination';           //样式名
    protected $maxButtonCount = 7;                 //数字控制页码最多显示几个(不计算首页和末页在内)
    protected $prevPageLabel = '上一页';            //上一页按扭显示文本
    protected $nextPageLabel = '下一页';            //下一页按扭显示文本
    protected $selectedPageCssClass = 'active';
    protected $prevPageCssClass = 'prev-page';
    protected $nextPageCssClass = 'next-page';
    protected $disabledCssClass = 'disabled';
    protected $disabledTag = 'span';

    /**
     * @param int $itemCount 总记录数
     * @param int $pageSize 每页显示多少条记录
     * @param null $currentPage 当前页码 默认取$_REQUEST['page']
     */
    public function __construct($itemCount = 0, $pageSize = 20, $currentPage = null)
    {
        $this->itemCount = $itemCount;
        $this->pageSize = $pageSize;
        $this->currentPage = $currentPage;
    }

    /**
     * 创建分页对象
     * @param int $currentPage 当前页码
     * @return static
     */
    public static function createFromCurrentNumber($currentPage)
    {
        $page = new static;
        $page->currentPage = (int)$currentPage;
        return $page;
    }

    /**
     * 生成页码超链接
     * @param string $baseUrl 未分页时的url
     * @param null $prefix 页码数字与$baseUrl之间的内容
     * @param null $suffix 页码数字之后的内容
     * @param null $firstPage 第一页的url
     * @return string
     */
    public function createLinks($baseUrl = '', $prefix = null, $suffix = null, $firstPage = null)
    {
        $this->url = $baseUrl;
        $this->prefix = ($prefix === null) ? '?' . urlencode($this->pageVar) . '=' : $prefix;
        $this->suffix = $suffix;

        if (is_null($firstPage)) {

            if (strpos($baseUrl, '?') !== false) {
                $firstPage = $baseUrl . '&' . ltrim($this->suffix, '&?');
            } else {
                $firstPage = $baseUrl . '?' . ltrim($this->suffix, '&?');
            }
            $firstPage = rtrim($firstPage, '?&');
        }

        $this->firstPage = $firstPage;
        return $this->createPageLinks();
    }

    /**
     * 注册默认的CSS样式
     */
    public function registerCss()
    {
        //Yii::app()->getClientScript()->registerCss('CPageDefaultStyle', $this->getDefaultCss());
    }

    public function __get($name)
    {
        $this->init();
        switch ($name) {
            case 'limit':
                return $this->offset . ', ' . $this->pageSize;
            default:
                return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function config($config)
    {
        if (is_array($config)) {
            foreach ($config as $k => $v) {
                $this->$k = $v;
            }
        }
    }

    /**
     * 初始化
     */
    protected function init()
    {
        //计算总页数
        $this->pageCount = ceil($this->itemCount / $this->pageSize);

        //当前页码
        if (empty($this->currentPage) && isset($_REQUEST[$this->pageVar])) {
            $this->currentPage = intval($_REQUEST[$this->pageVar]);
        }
        $this->currentPage = intval($this->currentPage);

        //最大页码判断
        //if($this->currentPage > $this->pageCount) {$this->currentPage = $this->pageCount;}

        //最小页码判断
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }

        //偏移量 (当前页-1)*每页条数
        $this->offset = ($this->currentPage - 1) * $this->pageSize;

        if ($this->currentPage > $this->pageCount) {
            $this->currentPage = $this->pageCount;
        }

        //上一页
        $this->prevPage = ($this->currentPage <= 1) ? 1 : $this->currentPage - 1;

        //下一页
        $this->nextPage = ($this->currentPage == $this->pageCount) ? $this->pageCount : $this->currentPage + 1;
    }

    /**
     * 生成页码超链接
     * @return string
     */
    protected function createPageLinks()
    {
        $this->init();

        if ($this->pageCount <= 1) {
            return '';
        }

        //当前页码的位置
        $cur = ceil($this->maxButtonCount / 10 * 3);

        //开始数字
        if ($this->currentPage <= $cur || $this->pageCount <= $this->maxButtonCount) {
            $ctrl_begin = 1;
        } else if ($this->currentPage > $this->pageCount - $this->maxButtonCount) {
            $ctrl_begin = $this->pageCount - $this->maxButtonCount + 1;
        } else {
            $ctrl_begin = $this->currentPage - $cur;
        }

        //结束数字
        $ctrl_end = $ctrl_begin + $this->maxButtonCount - 1;

        //不能大于总页数
        if ($ctrl_end > $this->pageCount) {
            $ctrl_end = $this->pageCount;
        }

        $ctrl_num_html = "";
        for ($i = $ctrl_begin; $i <= $ctrl_end; $i++) {
            if ($i == $this->currentPage) {
                //当前页，不加超链接
                $ctrl_num_html .= "<{$this->disabledTag} class='{$this->selectedPageCssClass}' >{$i}</{$this->disabledTag}>";
            } else {
                $url = $this->createPageLink($i);
                $ctrl_num_html .= "<a href='{$url}'>{$i}</a>";
            }
        }

        //判断是否需要加上省略号
        if ($ctrl_begin != 1) {
            $url = $this->createPageLink(1);
            $ctrl_num_html = "<a href='{$url}'>1</a><{$this->disabledTag}>...</{$this->disabledTag}>" . $ctrl_num_html;
        }
        if ($ctrl_end != $this->pageCount) {
            $url = $this->createPageLink($this->pageCount);
            $ctrl_num_html .= "<{$this->disabledTag}>...</{$this->disabledTag}><a href='{$url}'>{$this->pageCount}</a>";
        }

        //上一页
        if ($this->currentPage == 1) {
            $prev = "<{$this->disabledTag} class='{$this->prevPageCssClass} {$this->disabledCssClass}'>{$this->prevPageLabel}</{$this->disabledTag}>";
        } else {
            $url = $this->createPageLink($this->prevPage);
            $prev = "<a class='{$this->prevPageCssClass}' href='{$url}'>{$this->prevPageLabel}</a>";
        }

        //下一页
        if ($this->currentPage == $this->pageCount) {
            $next = "<{$this->disabledTag} class='{$this->nextPageCssClass} {$this->disabledCssClass}'>{$this->nextPageLabel}</{$this->disabledTag}>";
        } else {
            $url = $this->createPageLink($this->nextPage);
            $next = "<a class='{$this->nextPageCssClass}' href='{$url}'>{$this->nextPageLabel}</a>";
        }

        //控制翻页链接
        //<div class="digg"><span class="disabled"> < </span><span class="current">1</span><a href="#">2</a><a href="#">3</a><a href="#">4</a><a href="#">5</a><a href="#">6</a><a href="#">7</a>...<a href="#">199</a><a href="#">200</a><a href="#"> > </a></div>
        $html = "<span class=\"{$this->className}\">";
        $html .= $prev . ' ';
        $html .= $ctrl_num_html;
        $html .= ' ' . $next;
        $html .= "</span>";
        return $html;
    }

    protected function createPageLink($num)
    {
        if ($num == 1) {
            if ($this->firstPage !== null) {
                $suffix = ltrim($this->makeSuffix(), '&?');
                if (strlen($suffix) > 0) {
                    $suffix = strpos($this->firstPage, '?') !== false ? '&' : '?' . $suffix;
                }
                return $this->firstPage . $suffix;
            }
            return $this->url;
        }
        return $this->url . $this->prefix . $num . $this->makeSuffix();
    }

    protected function makeSuffix()
    {
        $suffix = '';
        if (is_null($this->suffix)) {

            if (strpos($this->url . $this->prefix, '?') !== false) {

                //去除$_GET中的page参数
                $get = isset($_GET) ? $_GET : array();
                unset($get['page']);
                $suffix = '&' . http_build_query($get);

            } else {

                $suffix = '?' . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');

            }
        }
        return rtrim($suffix, '?&');
    }

    public function getDefaultCss()
    {
        return <<<TAG
.pagination {
	padding:3px; margin:3px; text-align:center;
}
.pagination a,.pagination span {
	border:#dddddd 1px solid;
	text-decoration:none;
	color:#666666; padding: 5px 10px; margin-right:4px;
}
.pagination a:hover {
	border: #a0a0a0 1px solid;
}
.pagination .active {
	font-weight:bold; background-color:#f0f0f0;
}
.pagination .disabled {
	border:#f3f3f3 1px solid;
	color:#aaaaaa;
}
TAG;
    }
}
