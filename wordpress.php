<?php
/**
 * 将该段代码添加到wordpress主题文件中，首先执行下面一掉表创建语句
 * @author loveyu
 */

/**
 * 添加当前评论用户到列表中
 * @param $comment
 * @return mixed
 */
function add_comment_user_email($comment){
	$email = strtolower($comment['comment_author_email']);
	/**
	 * @var wpdb $wpdb
	 */
	global $wpdb;
	$sid = md5($email);
	/*
	CREATE TABLE IF NOT EXISTS `wp_avatar` (
	  `sid` char(32) NOT NULL,
	  `email` varchar(255) NOT NULL,
	  PRIMARY KEY (`sid`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	 */
	$out = $wpdb->query("select `sid` from `" . $wpdb->prefix . "avatar` where `sid`='{$sid}';");
	if($out == 0){
		$wpdb->insert($wpdb->prefix . "avatar", [
			'sid' => $sid,
			'email' => $email
		]);
	}
	//过滤非正常网址
	if(!filter_var($comment['comment_author_url'], FILTER_VALIDATE_URL)){
		$comment['comment_author_url'] = '';
	} else{
		$x = parse_url($comment['comment_author_url']);
		$comment['comment_author_url'] = $x['scheme'] . "://" . $x['host'] . "/";
	}
	return $comment;
}

add_action('preprocess_comment', 'add_comment_user_email');