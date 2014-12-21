<?php
/**
 * Wordpress 添加头像缓存，使用Gravatar和QQ邮箱
 * @author loveyu
 * @link http://www.loveyu.org/3154.html
 */
set_time_limit(1000);

/**
 * 数据库地址
 */
define("DB_HOST", "127.0.0.1");
/**
 * 数据库名
 */
define("DB_NAME", "wpdb");
/**
 * 数据库用户
 */
define("DB_USER", "root");
/**
 * 数据库密码
 */
define("DB_PWD", "123456");
/**
 * 数据库表名
 */
define("DB_TABLE", "wp_avatar");
/**
 * 默认头像跳转地址
 */
define('DefaultAvatar', "/avatar/default.jpg");
/**
 * 随机头像生成地址
 */
define('RAND_AVATAR', "http://www.loveyu.org/rand_avatar.php");

if(isset($_GET['email']) && filter_var(trim($_GET['email']), FILTER_VALIDATE_EMAIL)){
	$sid = md5(strtolower(trim($_GET['email'])));
} else{
	$sid = isset($_GET['id']) ? strtolower($_GET['id']) : "";
}
if(empty($sid)){
	//参数检查失败
	default_avatar();
	return;
}
if(is_file(__DIR__ . "/avatar/" . $sid . ".png")){
	//文件存在性检查
	header("location: /avatar/{$sid}.png");
	return;
}
try{
	if(preg_match("/^[0-9a-z]{32}$/", $sid) === 1){
		$ss = NULL;
		if(gravatar_check($sid)){
			//是否存在Gravatar头像
			$ss = file_get_contents(get_gravatar($sid));
		} else{
			//开始读取数据库中的邮箱地址
			$d = read_sql_email($sid);
			if($d){
				//进行QQ邮箱检查
				if(preg_match("/^[1-9]{1}[0-9]{4,11}@qq\\.com$/", $d) == 1){
					//开始读取QQ头像
					$index = strpos($d, "@");
					$ss = read_qq_avatar(substr($d, 0, $index));
				}
			}
		}
		if(empty($ss)){
			// 当QQ头像和Gravatar头像都为空时读取随机头像
			$ss = file_get_contents(get_rand_avatar($sid));
		}

		//开始写入头像内容
		$path = __DIR__ . "/avatar/" . $sid . ".png";
		file_put_contents($path, $ss);

		//头像大小检查与调整
		list($w, $h, $t) = getimagesize($path);
		if($w != $h){
			img_resize($path, $w, $h, $t);
		}

		//写入后，转到真实头像地址
		header("location: /avatar/{$sid}.png");
		return;
	} else{
		//如果参数检查失败，直接返回
		default_avatar();
	}
} catch(Exception $ex){
	//任何异常直接结束
	default_avatar();
}

/**
 * 进行默认头像地址跳转，并结束
 */
function default_avatar(){
	header("location: " . DefaultAvatar);
	die();
}

/**
 * 读取QQ头像的具体内容
 * @param string $u QQ号码
 * @return null|string 头像的内容
 */
function read_qq_avatar($u){
	$ss = NULL;
	foreach([100, 40] as $size){
		$ss = file_get_contents(get_qq_avatar_src($u, $size));
		if(!in_array(strlen($ss), [3707, 7097,])){
			break;
		}
		$ss = NULL;
	}
	return $ss;
}

/**
 * 获取随机头像的地址
 * @param $sid
 * @return string
 */
function get_rand_avatar($sid){
	return RAND_AVATAR."?hash=".$sid;
}

/**
 * 读取数据库中的邮件地址
 * @param $sid
 * @return null | string
 */
function read_sql_email($sid){
	$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PWD);
	$stmt = $pdo->prepare("select * from `" . DB_TABLE . "` where `sid`=:sid LIMIT 0,1;");
	$stmt->execute([':sid' => $sid]);
	$d = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$stmt->closeCursor();
	unset($pdo);
	if(isset($d[0]['email'])){
		return $d[0]['email'];
	} else{
		return NULL;
	}
}

/**
 * 读取指定的QQ头像下载地址
 * @param string $qq
 * @param int    $size
 * @return string
 */
function get_qq_avatar_src($qq, $size = 100){
	return "http://q1.qlogo.cn/g?b=qq&nk={$qq}&s={$size}&t=" . time();
}

/**
 * Gravatar头像存在性检查
 * @param $sid
 * @return bool
 */
function gravatar_check($sid){
	$url = "http://" . (hexdec($sid[0]) % 2) . ".gravatar.com/avatar/" . $sid . "?d=404&s=48";
	$headers = get_headers($url);
	if(isset($headers[0])){
		return strpos($headers[0], "200") !== false;
	}
	return false;
}

/**
 * 返回一个gravatar 头像地址
 * @param $sid
 * @return string
 */
function get_gravatar($sid){
	$host = sprintf("http://%d.gravatar.com", (hexdec($sid[0]) % 2));
	return "{$host}/avatar/{$sid}?s=100";
}

/**
 * 图像大小调整
 * @param string $src 原始路径
 * @param int    $w   宽
 * @param int    $h   高
 * @param int    $t   图像类型
 */
function img_resize($src, $w, $h, $t){
	$OldImage = NULL;
	switch($t){
		case 2:
			$OldImage = ImageCreateFromJpeg($src);
			break;
		case 6:
			$OldImage = ImageCreateFromwbmp($src);
			break;
		case 3:
			$OldImage = ImageCreateFromPng($src);
			break;
	}
	if($OldImage !== NULL){
		$NewThumb = ImageCreateTrueColor(100, 100);
		imagecopyresampled($NewThumb, $OldImage, 0, 0, 0, 0, 100, 100, $w, $h);
		imagedestroy($OldImage);
		switch($t){
			case 2:
				imagejpeg($NewThumb, $src);
				break;
			case 6:
				imagewbmp($NewThumb, $src);
				break;
			case 3:
				imagepng($NewThumb, $src);
				break;
		}
	}
}