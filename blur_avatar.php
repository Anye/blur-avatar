<?php
/**
 * 模糊化微信聊天记录截图
 *
 * 给聊天记录上方联系人昵称、右边头像、右边头像模糊化（for Jonns）
 *
 * @create 2015年4月8日00:00:00
 * 
 * @author AnyeGates <me@gatesanye.com>
 */

$proccess_dir = realpath('before');	// 源文件目录
$result_dir = __DIR__ . '/result';	// 处理后的图片存放目录

remove_avatar($proccess_dir, $result_dir);	// 开始处理！

/**
 * 遍历目录和处理里面所有文件
 * 
 * @param  string $proccess_dir 源目录
 * @param  string $result_dir   存放目录
 * @return void
 */
function remove_avatar($proccess_dir, $result_dir)
{
	list($dirs, $files)  = get_all_files_and_dirs($proccess_dir);
	foreach ($dirs as $dir) {	// 按照源目录结构创建结果的目录
		$dirname = $result_dir . $dir;
		echo "查询 {$dirname} 是否存在……\n";
		
		if (!is_dir($dirname)) {
			echo "创建目录 {$dirname}\n";
			mkdir($dirname, 0777, true);
		}
	}

	foreach ($files as $file) {	// 处理各个文件
		$filename = $proccess_dir . $file;
		$savename = $result_dir . $file;

		echo "处理 {$filename} => {$savename}\n";
		$ret = process_a_file($filename, $savename);
	}
}

/**
 * 遍历一个目录获得里面所有文件及文件夹
 * 
 * @param  string $dir 目录名
 * @return array(目录数组， 文件数组)
 */
function get_all_files_and_dirs($dir)
{
    if (!is_dir($dir)) return false; # 如果$dir变量不是一个目录，直接返回false        
    $dirs[] = '';     # 用于记录目录
    $files = array(); # 用于记录文件
    while (list($k, $path)=each($dirs)) {
        $absDirPath = "$dir/$path";     # 当前要遍历的目录的绝对路径
        $handle = opendir($absDirPath); # 打开目录句柄
        readdir($handle);               # 先调用两次 readdir() 过滤 . 和 ..
        readdir($handle);               # 避免在 while 循环中 if 判断
        while (false !== $item=readdir($handle)) {
            $relPath = "$path/$item";   # 子项目相对路径
            $absPath = "$dir/$relPath"; # 子项目绝对路径
            if (is_dir($absPath)) {       # 如果是一个目录，则存入到数组 $dirs
                $dirs[] = $relPath;
            } else  {                     # 否则是一个文件，则存入到数组 $files
                $files[] = $relPath;
            }
        }
        closedir($handle); # 关闭目录句柄
    }
    return array($dirs, $files);
}

/**
 * 处理一个文件
 * 
 * @param  string $filename 源文件名
 * @param  string $savename 处理后保存的名字（全路径）
 * @return boolean 成功 | 失败
 */
function process_a_file($filename, $savename)
{
	$d_width = 720;
	$avatar_width = 105;
	$fill_color = 0xEEEEEE;

	if (file_exists($filename)) {
		$info 	= pathinfo($filename);
		$ext 	= isset($info['extension']) ? $info['extension'] : 'jpg';
		switch ($ext) {
			case 'png':
				$s_image = imagecreatefrompng($filename);
				break;
			case 'gif':
				$s_image = imagecreatefromgif($filename);
				break;
			case 'jpg':
			case 'jpeg':
			default:
				$s_image = imagecreatefromjpeg($filename);
				break;
		}

		$s_width 	= imagesx($s_image);
		$s_height 	= imagesy($s_image);
		$d_height 	= intval($d_width * $s_height / $s_width);
		$d_image 	= imagecreatetruecolor($d_width, $d_height);

		imagecopyresized($d_image, $s_image, 0, 0, 0, 0, $d_width, $d_height, $s_width, $s_height);	// resize

		$d_image = draw_streak($d_image, 0, 130, 'h', $fill_color, 2);	// 给上边联系人名称加上条纹
		$d_image = draw_streak($d_image, 0, $avatar_width, 'v', $fill_color);	// 给左边联系人加上条纹
		$d_image = draw_streak($d_image, $d_width-$avatar_width, $d_width, 'v', $fill_color);	// 给右边联系人加上条纹

		switch ($ext) {
			case 'png':
				imagepng($d_image, $savename);
				break;
			case 'gif':
				imagegif($d_image, $savename);
				break;
			case 'jpg':
			case 'jpeg':
			default:
				imagejpeg($d_image, $savename);
				break;
		}
		imagedestroy($d_image);
		imagedestroy($s_image);

		echo "文件 {$filename} 处理成功！\n";
		return true;
	} else {
		echo "文件 {$filename} 不存在\n";
		return false;
	}
}

/**
 * 画条纹
 * 
 * @param  resource  $image      图像资源
 * @param  integer   $start      开始坐标
 * @param  integer   $end        结束坐标
 * @param  string    $direction  方向：h 水平，v 竖直
 * @param  integer   $color      条纹颜色
 * @param  integer   $streak_gap 条纹间距
 * @param  integer   $streak     条纹宽度（高度）
 * @return resource  已经画好条纹的图像
 */
function draw_streak($image, $start, $end, $direction='v', $color=0xEEEEEE, $streak_gap=4, $streak=10)
{
	$tmp = $start;
	$image_width = imagesx($image);
	$image_height= imagesy($image);

	if ($direction=='v') {
		$x_start = $start;
		$x_end	 = $end;

		while ($tmp <= $x_end) {
			$x1 = $tmp 	+ $streak_gap;
			$x2 = $x1 	+ $streak;
			if ( ($x1>$x_end) || ($x2>$x_end) ) return $image;
			imagefilledrectangle($image, $x1, 0, $x2, $image_height, $color);	// 画上竖直条纹
			$tmp = $x2;
		}
	} else {
		$y_start = $start;
		$y_end	 = $end;

		while ($tmp <= $y_end) {
			$y1 = $tmp 	+ $streak_gap;
			$y2 = $y1 	+ $streak;
			if ( ($y1>$y_end) || ($y2>$y_end) ) return $image;
			imagefilledrectangle($image, 0, $y1, $image_width, $y2, $color);	// 水平条纹
			$tmp = $y2;
		}
	}

	return $image;
}
