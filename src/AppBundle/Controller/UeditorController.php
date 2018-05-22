<?php
namespace AppBundle\Controller;

use Rain\Application;
use Rain\Request;
use Rain\Url;


/**
 * UEditor上传处理类
 * @author Ma Yanlong
 * @since  1.0
 */
class UeditorController
{

    /**
     * ueditor上传入口
     * @param Request $request
     */
    public function anyUpload(Request $request)
    {
        //获取配置文件
        $path =  rtrim($_SERVER['DOCUMENT_ROOT'] , '/\\') . Url::asset('static/ueditor/config.json');
        $config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($path) ), true);

        $action = $_GET['action'];

        switch ($action) {
            case 'config':
                $result =  json_encode($config);
                echo $result;
                break;
            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = self::processUpload($config, $request);
                echo json_encode($result);
                break;
            default:
                $result = json_encode(array(
                    'state'=> '请求地址出错'
                ));
                break;
        }
    }

    /**
     * 处理上传
     * @param $config
     * @param Request $request
     * @return bool
     */
    public function processUpload($config, Request $request)
    {
        // 允许上传文件大小 (M)
        $maxSize = 2;
        // 允许上传的扩展名
        $extensionName = array('jpg', 'jpeg', 'png', 'gif');

        // 上传目录 例如: 'uploads/' . @date('Ym') . '/' . @date('d') . '/';
        $targetPath = @date('Ym') . '/' . @date('d') . '/';
        $subPath = 'uploads/ueditor/' . $targetPath;

        $baseUrl = $request->getBasePath();
        $basePath = './';

        $result = array(
            'status' => 0, //1成功 0失败
            'message' => '',
            'files' => array()
        );

        $name = $config['imageFieldName'];  //input file 的name
        $files = $request->files->get($name);
        if ($files === null) {
            $result['message'] = '没有上传文件';
            return json_encode($result);
        }

        if (!is_array($files)) {
            $files = array($files);
        }

        foreach ($files as $file) {

            $originalExtension = $file->getClientOriginalExtension();

            //检查文件大小
            if ($file->getClientSize() > $maxSize * 1024 * 1024) {
                $result['message'] = '文件大小不能超过' . $maxSize . 'M';
                break;
            }

            //检查扩展名
            $allowExt = array_map('strtolower', $extensionName);
            if (!in_array(strtolower($originalExtension), $allowExt)) {

                if (empty($originalExtension)) {
                    $result['message'] = '不允许上传没有扩展名的文件';
                } else {
                    $result['message'] = '不允许上传"' . $originalExtension . '"格式的文件';
                }
                break;
            }

            //保存上传的文件
            try {

                //文件名
                $basename = uniqid() . '.' . $originalExtension;

                $file->move($basePath . $subPath, $basename);
                //文件信息 name basename  subPath size type url thumbnailUrl(数组)
                $arr = array(
                    "state" => 'SUCCESS',		//状态
                    'url' => $baseUrl . '/' . $subPath . $basename,
                    "title" => $basename,			//上传到服务器的文件名
                    "original" => $file->getClientOriginalName(),	//上传前客户端的文件名
                    "type" => $file->getClientMimeType(),		// image/jpeg
                    "size" => $file->getClientSize()
                );
                $result['files'][] = $arr;

            } catch (\Exception $ex) {
                $result['message'] = $ex->getMessage();
                break;
            }
        }

        //上传成功
        if (count($result['files']) > 0) {
            //修改状态为成功
            $result['status'] = 1;
            return $result['files'][0];
        } else {
            return false;
        }
    }

}