<?php if (!defined('__TYPECHO_ROOT_DIR__')) {
	/*
 * @Author       : lovefc
 * @Date         : 2025-02-02 22:19:05
 * @LastEditTime : 2025-02-17 22:24:46
 */
	exit;
} ?>
<!DOCTYPE HTML>
<html>

<head>
	<meta charset="<?php $this->options->charset(); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no,viewport-fit=cover,minimum-scale=1,maximum-scale=1,user-scalable=no">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="renderer" content="webkit">
	<meta name="layoutmode" content="standard">
	<meta name="imagemode" content="force">
	<meta name="wap-font-scale" content="no">
	<link rel="icon" type="image/x-icon" href="<?php $this->options->themeUrl('favicon.ico'); ?>">
	<title><?php $this->archiveTitle([
				'category' => _t('分类 %s 下的文章'),
				'search'   => _t('包含关键字 %s 的文章'),
				'tag'      => _t('标签 %s 下的文章'),
				'author'   => _t('%s 发布的文章')
			], '', ' - '); ?><?php $this->options->title(); ?></title>
	<?php echoHeader(); ?>		
	<link rel="stylesheet" href="<?php $this->options->themeUrl('css/normalize.css'); ?>">
	<link rel="stylesheet" href="<?php $this->options->themeUrl('css/style.css'); ?>">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
	<?php if ($this->options->fontcdn): ?>
	<link href="<?php $this->options->fontcdn(); ?>" rel="stylesheet">
	<?php endif; ?>
	<style>
		body {
			<?php if ($this->options->fontname): ?>
			font-family: '<?php $this->options->fontname(); ?>', "Microsoft YaHei", "黑体", "宋体", sans-serif;
			<?php else: ?>
			font-family: arial,verdana,"microsoft yahei";
			<?php endif; ?>
			<?php if ($this->options->background): ?>background-image: url('<?php $this->options->background(); ?>') <?php endif; ?>
		}
	</style>
</head>