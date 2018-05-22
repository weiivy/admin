<?php

namespace PHPExcel;

include_once dirname(__FILE__) . '/PHPExcel.php'; // 让PHPExcel内部自动加载生效

/**
 * Excel操作类
 * @author  Zou Yiliang <it9981@gmail.com>
 * @since   1.0
 */
class Excel
{
    private $tempPath;

    /**
     * @param string $tempPath 临时目录，需要有可写权限
     */
    public function __construct($tempPath = null)
    {
        $this->tempPath = $tempPath;
    }

    /**
     * 导出到Excel
     *
     * 使用示例
     *
     * $data = array(
     *     array('id' => 1, 'name' => 'Jack', 'age' => 18),
     *     array('id' => 2, 'name' => 'Mary', 'age' => 20),
     *     array('id' => 3, 'name' => 'Ethan', 'age' => 34),
     * );
     * $map = array(
     *     'id' => '编号',
     *     'name' => '姓名',
     *     'age' => '年龄',
     * );
     * $file = 'user' . date('Y-m-d');
     * $excel = new \PHPExcel\Excel($runtimePath); // $runtimePath = \Rain\Application::$app['path'] . '/runtime';
     * $excel->exportExcel($data, $map, $file, '用户信息');
     *
     *
     * @param array $data 需要导出的数据
     * @param array $map 标题 格式如 array('id'=>'编号','name'=>'姓名')
     * @param string $filename 下载显示的默认文件名
     * @param string $title 工作表名称
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function exportExcel($data, $map = array(), $filename = '', $title = 'Worksheet')
    {
        if (!is_array($data)) {
            return;
        }
        if (count($data) < 1) {
            return;
        }

        //单元格缓存到PHP临时文件中
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array('memoryCacheSize' => '500MB');
        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        //实例化工作簿对象
        $objPHPExcel = new \PHPExcel();
        //获取活动工作表
        $objActSheet = $objPHPExcel->getActiveSheet();
        //设置工作表的标题
        $objActSheet->setTitle($title);

        //第一行为标题
        $col = 0;

        foreach ($data[0] as $key => $value) {
            if (array_key_exists($key, $map)) {
                $title = $map[$key];
            } else {
                $title = $key;
            }
            $objActSheet->getCellByColumnAndRow($col, 1)->setValue($title);
            $col++;
        }

        //第2行开始是内容
        $row = 2;
        foreach ($data as $v) {
            //第一列序号
            //$objActSheet->getCellByColumnAndRow(0,$row)->setValue($row-1);
            $col = 0;
            foreach ($v as $key => $value) {
                $objActSheet->getCellByColumnAndRow($col, $row)->setValueExplicit($value, \PHPExcel_Cell_DataType::TYPE_STRING);
                $col++;
            }
            $row++;
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        //保存到文件
        $file = uniqid() . '.xls';

        $savePath = $this->tempPath;

        $objWriter->save($savePath . $file);

        if (empty($filename)) {
            $filename = date('YmdHis');
        }

        if (strtolower(substr($filename, -4)) != ".xls") {
            $filename .= '.xls';
        }

        //弹出下载对话框
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . filesize($savePath . $file));

        readfile($savePath . $file);

        //删除临时文件
        unlink($savePath . $file);
    }


    /**
     * 读取Excel文件数据
     *
     * 使用示例
     *
     * $map = array(
     *     'id' => '编号',
     *     'name' => '姓名',
     *     'age' => '年龄',
     *  );
     * $excel = new \PHPExcel\Excel();
     * $data = $excel->readExcelFile('./user2014-11-08.xls', $map);
     *
     * @param $file
     * @param array $map
     * @param int $titleRow 标题在第几行
     * @param int $beginColumn 从行几列开始
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function readExcelFile($file, $map = array(), $titleRow = 1, $beginColumn = 1)
    {
        //单元格缓存到PHP临时文件中
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
        $cacheSettings = array('memoryCacheSize' => '500MB');
        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        //$excelReader = \PHPExcel_IOFactory::createReader('Excel5');
        $excelReader = \PHPExcel_IOFactory::createReaderForFile($file);

        //读取excel文件中的第一个工作表
        $phpExcel = $excelReader->load($file)->getSheet(0);

        //取得最大的行号
        $total_line = $phpExcel->getHighestRow();

        //取得最大的列号
        $total_column = $phpExcel->getHighestColumn();
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($total_column);


        //将列名与map对应
        $title = array();
        if ($titleRow > 0) {
            for ($cols = $beginColumn - 1; $cols < $highestColumnIndex; $cols++) {
                $val = $phpExcel->getCellByColumnAndRow($cols, $titleRow)->getValue();

                $field = array_search($val, $map);
                if ($field === false) {
                    $field = trim($val);
                }
                $title[] = $field;

            }
        }

        $data = array();
        $row = 0;
        for ($currentRow = $titleRow + 1; $currentRow <= $total_line; $currentRow++) {
            $i = 0;
            for ($cols = $beginColumn - 1; $cols < $highestColumnIndex; $cols++) {
                //单元格数据
                $val = $phpExcel->getCellByColumnAndRow($cols, $currentRow)->getValue();

                $field = isset($title[$i]) ? $title[$i] : $i;
                $data[$row][$field] = trim($val);
                $i++;
            }
            $row++;
        }
        return $data;
    }

    /**
     * 将Excel文件以Html格式返回
     * @param $file
     * @return string
     */
    public function toHTML($file)
    {
        include_once dirname(__FILE__) . '/Excel_HTML.php';

        //单元格缓存到PHP临时文件中
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
        $cacheSettings = array('memoryCacheSize' => '500MB');
        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        //$excelReader = PHPExcel_IOFactory::createReader('Excel5');
        $excelReader = \PHPExcel_IOFactory::createReaderForFile($file);
        //读取excel文件中的第一个工作表
        $phpExcel = $excelReader->load($file);
        $excelHTML = new \Excel_HTML($phpExcel);
        return $excelHTML->ToHtml();
    }

}