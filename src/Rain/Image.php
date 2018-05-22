<?php

namespace Rain;

/**
 * 图片处理类
 *
 * 功能列表：
 * 1.生成缩略图 thumb
 * 2.图片裁剪 customCut
 * 3.图片加水印 waterMark
 * 4.转换图片格式 convert
 * 5.获取图片尺寸 getSize
 * 6.剪裁并生成缩略图(先按比例切掉边上多余的部份) cutThumb
 *
 * @author Zou Yiliang <it9981@gmail.com>
 */
class Image
{
    /**
     * 生成缩略图
     * @param string $filename 待处理的图片名
     * @param int $width 缩略图宽度
     * @param int $height 缩略图高度
     * @param string $fix 缩略图文件名前缀
     * @param string $dir 缩略图保存目录
     * @param string $suffix 缩略图文件名后缀
     * @param string $saveExt 缩略图扩展名 如 .jpg
     * @return bool|string 成功返回新的文件名,失败返回false
     * @date 20141213
     */
    public static function thumb($filename, $width = 100, $height = 100, $fix = 's_', $dir = './', $suffix = '', $saveExt = '')
    {
        //创建原图画板
        $src_img = self::createImage($filename);
        if ($src_img == false) {
            return false;
        }

        //扩展名，类似于 .jpg
        $ext = strtolower(strrchr($filename, '.'));
        if ($saveExt == '') {
            $saveExt = $ext;
        }
        $saveExt = '.' . trim($saveExt, '.');

        //目标图像画板
        $dst_img = imagecreatetruecolor($width, $height);

        //填充为白色
        imagefill($dst_img, 0, 0, imagecolorallocate($dst_img, 255, 255, 255));

        //获到源图片大小
        $x = imagesx($src_img); //最大x坐标值
        $y = imagesy($src_img); //最大y坐标值

        //计算图像比例
        $dst_x = 0;
        $dst_y = 0;
        if ($x / $y > $width / $height) {
            //如果图片比较宽,计算目标高度 (小图的高度)
            $h = $width * $y / $x;
            //如果图片比较宽，我们就用(目标的高度-计算出的高度)/2,用为目标的y偏移量
            $dst_y = ($height - $h) / 2;
            //宽度使用指定要求缩放的宽度
            $w = $width;
        } else {
            $w = $x * $height / $y; //目标宽度 (小图的宽度)
            $h = $height;
            $dst_x = ($width - $w) / 2;
        }

        //复制图片
        imagecopyresampled($dst_img, $src_img,
            $dst_x, $dst_y,   // 目标起始点
            0, 0,             // 原图起始点
            $w, $h,           // 目标宽高
            $x, $y)           // 原图宽高
        ;

        //保存文件名
        $dir = rtrim($dir, '/\\') . '/';
        $basename = basename($filename);
        $realname = str_replace($ext, '', $basename);
        $save = $dir . $fix . $realname . $suffix . $saveExt;

        //释放资源
        imagedestroy($src_img);

        //保存到文件
        if (self::saveToFile($dst_img, $save)) {

            //释放资源
            imagedestroy($dst_img);

            return $fix . $realname . $suffix . $saveExt;
        }
        return false;
    }

    /**
     * 图片自定义裁剪
     * @param string $from_name 待处理的图片名
     * @param string $dst_name 生成的目标文件
     * @param string $x 想要得到的图片高度
     * @param string $y 新文件名的前缀
     * @param string $w 新文件保存到哪个目录中
     * @param string $h 新文件保存到哪个目录中
     * @return  string|bool 成功返回文件名  失败返回false
     */
    public static function customCut($from_name, $dst_name, $x, $y, $w, $h)
    {
        //创建原图画板
        $src_img = self::createImage($from_name);
        if ($src_img == false) {
            return false;
        }

        //目标图像 (画板)
        $dst_img = imagecreatetruecolor($w, $h);

        //填充为白色
        imagefill($dst_img, 0, 0, imagecolorallocate($dst_img, 255, 255, 255));

        //复制图片
        imagecopyresampled($dst_img, $src_img, 0, 0, $x, $y, $w, $h, $w, $h);

        //保存到文件
        if (self::saveToFile($dst_img, $dst_name)) {

            //释放资源
            imagedestroy($src_img);
            imagedestroy($dst_img);

            return $dst_name;
        }
        return false;
    }

    /**
     * 图片加水印
     *
     * 使用示例
     * $result=water('images/test.jpg','demo.png',1,'w_','../');
     * var_dump($result);
     *
     * @param $filename string 原始图片文件名
     * @param $water string 水印图片
     * @param $pos int 水印位置  1右下  2 中中  3左上
     * @param $prefix string 新文件名前缀
     * @param $path string 新文件保存的目录，如果不指定，则保存在原图的目录中
     * @return string 新图片文件名 失败返回false
     */
    public static function waterMark($filename, $water, $pos = 1, $prefix = 'w_', $path = null)
    {
        //图片画板
        $img = self::createImage($filename);
        $img_w = self::createImage($water);
        if ($img == false || $img_w == false) {
            return false;
        }

        //原图大小
        $src_x = imagesx($img);
        $src_y = imagesy($img);

        //获到水印图片的大小
        $x = imagesx($img_w);
        $y = imagesy($img_w);

        //根据$pos来决定目标的位置
        switch ($pos) {
            case 1:
                $w = $src_x - $x;
                $h = $src_y - $y;
                break;
            case 2:
                $w = ($src_x - $x) / 2;
                $h = ($src_y - $y) / 2;
                break;
            case 3:
                $w = 0;
                $h = 0;
                break;
        }

        //将水印复制到另一个画板中
        imagecopyresampled($img, $img_w, $w, $h, 0, 0, $x, $y, $x, $y);

        //输出画板
        $new = $prefix . basename($filename);
        if ($path == null) {
            $dir = dirname($filename);
        } else {
            $dir = $path;
        }
        $dir = rtrim($dir, '/') . '/';
        $newfilename = $dir . $new;

        //保存文件
        $result = self::saveToFile($img, $newfilename);

        //释放资源
        imagedestroy($img);
        imagedestroy($img_w);

        if ($result) {
            return $new;
        } else {
            return false;
        }
    }

    /**
     * 转换图片格式 例如将1.png 转为 1.jpg
     * @param $file
     * @param $saveFile
     * @return bool
     */
    public static function convert($file, $saveFile)
    {
        $img = self::createImage($file);
        if ($img == false) {
            return false;
        }
        if (self::saveToFile($img, $saveFile)) {
            imagedestroy($img);
            return true;
        }
        return false;
    }

    /**
     * 获取图片宽高
     * @param $imgfile
     * @return array|bool 返回数组 array('width'=>宽度,'height'=>高度);
     */
    public static function getSize($imgfile)
    {
        $img = self::createImage($imgfile);
        if ($img == false) {
            return false;
        }
        $x = imagesx($img);
        $y = imagesy($img);
        imagedestroy($img);
        return array('width' => $x, 'height' => $y);
    }


    /**
     * 保存img资源到文件
     * @param $img
     * @param $file
     * @return bool
     */
    public static function saveToFile($img, $file)
    {
        $ext = strtolower(strrchr($file, '.'));
        switch ($ext) {
            case '.jpg':
            case '.jpeg':
                $fun = 'imagejpeg';
                break;
            case '.png':
                $fun = 'imagepng';
                break;
            case '.gif':
                $fun = 'imagegif';
                break;
            default:
                return false;
        }

        @mkdir(dirname($file), 0777, true);
        @chmod(dirname($file), 0777);

        //保存到文件
        return $fun($img, $file);
    }

    /**
     * 根据文件名，返回画板资源
     * @param $filename
     * @return bool|resource
     */
    public static function createImage($filename)
    {
        $arr = @getimagesize($filename);

        if ($arr === false) {
            return false;
        }

        //1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
        switch ($arr[2]) {
            case 1:
                return imagecreatefromgif($filename);
            case 2:
                return imagecreatefromjpeg($filename);
            case 3:
                return imagecreatefrompng($filename);
            default:
                return false;
        }
    }


    /**
     * 剪裁并生成缩略图(先按比例切掉边上多余的部份)
     * @param $fromName 文件完整路径名
     * @param $dstName  文件保存路径名
     * @param $dstWidth 文件剪裁宽度
     * @param $dstHeight 文件剪裁高度
     * @return bool
     */
    public static function cutThumb($fromName, $dstName, $dstWidth, $dstHeight)
    {

        //需要的宽高比例
        $newPercent = $dstWidth / $dstHeight;//例如 200/100

        //当前图片实际尺寸
        $imgArr = self::getSize($fromName);

        //当前图片宽高比例
        $currentPercent = $imgArr['width'] / $imgArr['height'];  //例如  600/200    可 200/600


        //确定切割哪条边(分析图片太宽了，还是太高了)
        if ($currentPercent > $newPercent) {

            //太宽了 计算需要的宽度
            $w = $imgArr['height'] * $newPercent;

            //高度不变
            $h = $imgArr['height'];

            //切割点
            $x = (int)($imgArr['width'] - $w) / 2;
            $y = 0;


        } else {
            // 太高了 计算需要的高度
            $h = $imgArr['width'] / $newPercent;

            //宽度不变
            $w = $imgArr['width'];

            //切割点
            $y = (int)($imgArr['height'] - $h) / 2;
            $x = 0;

        }

        $basePath = dirname($dstName);
        if (!file_exists($basePath)) {
            @mkdir($basePath, 0777, true);
            @chmod($basePath, 0777);
        }

        if ($newPercent === $currentPercent) {
            copy($fromName, $dstName);//不用剪裁
        } else {
            self::customCut($fromName, $dstName, $x, $y, $w, $h);
        }

        //缩放
        return false !== self::thumb($dstName, $dstWidth, $dstHeight, '', $basePath);

    }
}
