<?php
namespace Rain;

/**
 * 文件上传类
 * 支持多文件上传
 *
 * $config = [
 *      //'baseUrl' => 'http://www.example.com', //默认为空时生成相对url，当需要返回绝对url时请设置此项
 *      'subPath' => 'temp/' . @date('Ym') . '/' . @date('d'),
 *      'thumb' => array(
 *          '100' => array('w' => 100, 'h' => 100, 'cut' => true),
 *      ),
 * ];
 *
 * $up = new UploadedFile($config);
 * $up->doUpload('file');
 * return json_encode($up->getFiles()); //多文件
 * return json_encode($up->getFile()); //单文件
 *
 */
class UploadedFile
{
    //根目录 默认为入口文件所在目录  './'
    public $rootPath;

    //指向根目录的url
    public $baseUrl;

    //上传目录 'uploads/'
    public $basePath;

    //子目录 默认以日期作为子目录 '201504/30/'
    public $subPath;

    //单位 M
    public $maxSize = 8;

    //允许上传的扩展名
    public $extensionName = array('jpg', 'jpeg', 'png', 'gif');

    //缩略图 在上传文件所在目录中, 以key作为子目录, 文件名相同. cut为true时, 将裁掉多余的部份, 否则补白
    public $thumb = array(
        // '100' => array('w' => 100, 'h' => 100, 'cut' => true),
        // '200' => array('w' => 200, 'h' => 200),
    );

    private $message;
    private $files = array();
    private $request;

    public function __construct($config = array())
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }

        $this->request = Application::$app->getRequest();

        if ($this->baseUrl === null) {
            $this->baseUrl = $this->request->getBasePath();
        } else {
            $this->baseUrl = rtrim($this->baseUrl, '/');
        }

        if ($this->rootPath === null) {
            $this->rootPath = realpath('.') . '/';
        } else {
            $this->rootPath = trim($this->rootPath, '/\\') . '/';
        }

        if ($this->subPath === null) {
            $this->subPath = @date('Ym') . '/' . @date('d') . '/';
        } else {
            $this->subPath = trim($this->subPath, '/\\') . '/';
        }

        if ($this->basePath === null) {
            $this->basePath = 'uploads' . '/';
        } else {
            $this->basePath = trim($this->basePath, '/\\') . '/';
        }
    }

    /**
     * 错误信息
     * @return string
     */
    public function getError()
    {
        return $this->message;
    }

    /**
     * 文件上传后的信息
     *
     * @return array
     *
     *  array(
     *       array (
     *           'name' => 'test.jpg',                               // 上传前客户端的文件名
     *           'basename' => '5541b4a04f05a.jpg',                  // 上传到服务器的文件名
     *           'basePath' => 'uploads/',                           // 上传总目录
     *           'subPath' => '201504/30/',                          // 子目录
     *           'size' => 327011,                                   // 文件大小
     *           'type' => "image/jpeg",                             // 类型
     *           'url' => '/uploads/201504/30/5541b4a04f05a.jpg',    // 访问url
     *           'thumbnailUrl' => array(
     *               '100' => '/uploads/201504/30/100/5541b4a04f05a.jpg',
     *               '200' => '/uploads/201504/30/200/5541b4a04f05a.jpg',
     *           )
     *      ),
     * )
     *
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function getFile()
    {
        return current($this->files);
    }

    /**
     * 执行上传操作
     * @param string $file 表单字段名
     * @return bool
     */
    public function doUpload($file = 'files')
    {
        $request = $this->request;

        // 允许上传文件大小 (M)
        $maxSize = $this->maxSize;

        // 允许上传的扩展名
        $extensionName = $this->extensionName;

        //$request->files
        //Symfony\Component\HttpFoundation\FileBag

        //$request->files->get('files'))
        //null 、array 、Symfony\Component\HttpFoundation\File\UploadedFile

        $files = $request->files->get($file);
        if ($files === null) {
            $this->message = '没有上传文件';
            return false;
        }

        if (!is_array($files)) {
            $files = array($files);
        }

        foreach ($files as $file) {
            if ($file == null) {
                continue;
            }
            $originalExtension = $file->getClientOriginalExtension();

            //检查文件大小
            if ($file->getClientSize() > $maxSize * 1024 * 1024) {
                $this->message = '文件大小不能超过' . $maxSize . 'M';
                return false;
            }

            //检查扩展名
            $allowExt = array_map('strtolower', $extensionName);
            if (!in_array(strtolower($originalExtension), $allowExt)) {

                if (empty($originalExtension)) {
                    $this->message = '不允许上传没有扩展名的文件';
                } else {
                    $this->message = '不允许上传"' . $originalExtension . '"格式的文件';
                }
                return false;
            }
        }

        foreach ($files as $file) {
            if ($file == null) {
                continue;
            }
            $originalExtension = $file->getClientOriginalExtension();

            //保存上传的文件
            try {

                //文件名
                $basename = uniqid() . '.' . $originalExtension;

                $file->move($this->rootPath . $this->basePath . $this->subPath, $basename);

                $arr = array(
                    'name' => $file->getClientOriginalName(),   // 上传前客户端的文件名 test.jpg
                    'basename' => $basename,                    // 上传到服务器的文件名 4eed004057dc.jpg
                    'basePath' => $this->basePath,              // 总目录 uploads/
                    'subPath' => $this->subPath,                // 子目录 201502/26/
                    'size' => $file->getClientSize(),           // 16556
                    'type' => $file->getClientMimeType(),       // image/jpeg
                    'url' => $this->baseUrl . '/' . $this->basePath . $this->subPath . $basename,
                    'thumbnailUrl' => array(),
                );

                //生成缩略
                $fullName = $this->rootPath . $this->basePath . $this->subPath . $basename;
                foreach ($this->thumb as $key => $size) {

                    //缩略图子目录
                    $thumbPath = $key . '/';

                    if (isset($size['cut']) && $size['cut']) {
                        $bool = Image::cutThumb($fullName, dirname($fullName) . '/' . $thumbPath . $basename, $size['w'], $size['h']);
                        if ($bool) {
                            $arr['thumbnailUrl'][$key] = $this->baseUrl . '/' . $this->basePath . $this->subPath . $thumbPath . $basename;
                        }
                    } else {
                        $bool = Image::thumb($fullName, $size['w'], $size['h'], '', dirname($fullName) . '/' . $thumbPath);
                        if ($bool) {
                            $arr['thumbnailUrl'][$key] = $this->baseUrl . '/' . $this->basePath . $this->subPath . $thumbPath . $basename;
                        }
                    }
                }

                $this->files[] = $arr;
            } catch (\Exception $ex) {
                $this->message = $ex->getMessage();
                return false;
            }
        }
        return true;
    }
}