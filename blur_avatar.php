<?php
/**
 * ģ����΢�������¼��ͼ
 *
 * �������¼�Ϸ���ϵ���ǳơ��ұ�ͷ���ұ�ͷ��ģ������for Jonns��
 *
 * @create 2015��4��8��00:00:00
 * 
 * @author AnyeGates <me@gatesanye.com>
 */

$proccess_dir = realpath('before');	// Դ�ļ�Ŀ¼
$result_dir = __DIR__ . '/result';	// ������ͼƬ���Ŀ¼

remove_avatar($proccess_dir, $result_dir);	// ��ʼ����

/**
 * ����Ŀ¼�ʹ������������ļ�
 * 
 * @param  string $proccess_dir ԴĿ¼
 * @param  string $result_dir   ���Ŀ¼
 * @return void
 */
function remove_avatar($proccess_dir, $result_dir)
{
	list($dirs, $files)  = get_all_files_and_dirs($proccess_dir);
	foreach ($dirs as $dir) {	// ����ԴĿ¼�ṹ���������Ŀ¼
		$dirname = $result_dir . $dir;
		echo "��ѯ {$dirname} �Ƿ���ڡ���\n";
		
		if (!is_dir($dirname)) {
			echo "����Ŀ¼ {$dirname}\n";
			mkdir($dirname, 0777, true);
		}
	}

	foreach ($files as $file) {	// ��������ļ�
		$filename = $proccess_dir . $file;
		$savename = $result_dir . $file;

		echo "���� {$filename} => {$savename}\n";
		$ret = process_a_file($filename, $savename);
	}
}

/**
 * ����һ��Ŀ¼������������ļ����ļ���
 * 
 * @param  string $dir Ŀ¼��
 * @return array(Ŀ¼���飬 �ļ�����)
 */
function get_all_files_and_dirs($dir)
{
    if (!is_dir($dir)) return false; # ���$dir��������һ��Ŀ¼��ֱ�ӷ���false        
    $dirs[] = '';     # ���ڼ�¼Ŀ¼
    $files = array(); # ���ڼ�¼�ļ�
    while (list($k, $path)=each($dirs)) {
        $absDirPath = "$dir/$path";     # ��ǰҪ������Ŀ¼�ľ���·��
        $handle = opendir($absDirPath); # ��Ŀ¼���
        readdir($handle);               # �ȵ������� readdir() ���� . �� ..
        readdir($handle);               # ������ while ѭ���� if �ж�
        while (false !== $item=readdir($handle)) {
            $relPath = "$path/$item";   # ����Ŀ���·��
            $absPath = "$dir/$relPath"; # ����Ŀ����·��
            if (is_dir($absPath)) {       # �����һ��Ŀ¼������뵽���� $dirs
                $dirs[] = $relPath;
            } else  {                     # ������һ���ļ�������뵽���� $files
                $files[] = $relPath;
            }
        }
        closedir($handle); # �ر�Ŀ¼���
    }
    return array($dirs, $files);
}

/**
 * ����һ���ļ�
 * 
 * @param  string $filename Դ�ļ���
 * @param  string $savename ����󱣴�����֣�ȫ·����
 * @return boolean �ɹ� | ʧ��
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

		$d_image = draw_streak($d_image, 0, 130, 'h', $fill_color, 2);	// ���ϱ���ϵ�����Ƽ�������
		$d_image = draw_streak($d_image, 0, $avatar_width, 'v', $fill_color);	// �������ϵ�˼�������
		$d_image = draw_streak($d_image, $d_width-$avatar_width, $d_width, 'v', $fill_color);	// ���ұ���ϵ�˼�������

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

		echo "�ļ� {$filename} ����ɹ���\n";
		return true;
	} else {
		echo "�ļ� {$filename} ������\n";
		return false;
	}
}

/**
 * ������
 * 
 * @param  resource  $image      ͼ����Դ
 * @param  integer   $start      ��ʼ����
 * @param  integer   $end        ��������
 * @param  string    $direction  ����h ˮƽ��v ��ֱ
 * @param  integer   $color      ������ɫ
 * @param  integer   $streak_gap ���Ƽ��
 * @param  integer   $streak     ���ƿ�ȣ��߶ȣ�
 * @return resource  �Ѿ��������Ƶ�ͼ��
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
			imagefilledrectangle($image, $x1, 0, $x2, $image_height, $color);	// ������ֱ����
			$tmp = $x2;
		}
	} else {
		$y_start = $start;
		$y_end	 = $end;

		while ($tmp <= $y_end) {
			$y1 = $tmp 	+ $streak_gap;
			$y2 = $y1 	+ $streak;
			if ( ($y1>$y_end) || ($y2>$y_end) ) return $image;
			imagefilledrectangle($image, 0, $y1, $image_width, $y2, $color);	// ˮƽ����
			$tmp = $y2;
		}
	}

	return $image;
}
