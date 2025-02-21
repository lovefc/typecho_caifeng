<?php
/*
 * @Author       : lovefc
 * @Date         : 2025-02-02 22:19:05
 * @LastEditTime : 2025-02-17 22:25:01
 */

/**
 * 采风-既见君子，云胡不喜
 * 
 * @package Typecho Theme CaiFeng
 * @author lovefc
 * @version 1.0.0
 * @link https://github.com/lovefc/typecho_caifeng
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<?php
// 获取带文章数的所有分类（包含空分类）
$allCategories = getCategories(array(
    'ignoreEmpty' => false
));
?>
<?php $this->need('page/header.php'); ?>

<body>
    <!-- 加载动画 -->
    <!--
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>
    -->
    <div class="container">
        <div class="profile">
            <?php if ($this->options->logoUrl): ?>
                <a id="logo" href="<?php $this->options->siteUrl(); ?>">
                    <img src="<?php $this->options->logoUrl() ?>" alt="<?php $this->options->title() ?>" />
                </a>
            <?php else: ?>
                <a id="logo" href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title() ?></a>
            <?php endif; ?>
        </div>
        <div class="contact-info">
            <p><?php if ($this->options->detail): ?> <?php $this->options->detail(); ?> <?php endif; ?></p>
        </div>
        <div class="categories">
            <div class="category"><a href="<?php $this->options->siteUrl(); ?>">全部</a></div>
            <?php foreach ($allCategories as $v) { ?>
                <div class="category"><a href="<?php echo $v['permalink']; ?>"><?php echo $v['name'] ?></a></div>
            <?php } ?>
        </div>
        <div class="search-container">
            <form class="search-form" method="post" action="<?php $this->options->siteUrl(); ?>">
                <input
                    type="search" id="s" name="s"
                    placeholder="输入关键词..."
                    class="search-input"
                    autocomplete="off">
                <button type="submit" class="search-btn">
                    <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18">
                        <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 0 0 1.48-5.34c-.47-2.78-2.79-5-5.59-5.34a6.505 6.505 0 0 0-7.27 7.27c.34 2.8 2.56 5.12 5.34 5.59a6.5 6.5 0 0 0 5.34-1.48l.27.28v.79l4.25 4.25c.41.41 1.08.41 1.49 0 .41-.41.41-1.08 0-1.49L15.5 14zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                    </svg>
                </button>
            </form>
        </div>
        <div id="article-list">
            <?php if ($this->have()): ?>
                <?php while ($this->next()): ?>
                    <?php $background_image = getFirstImageSrc($this->content); ?>
                    <div class="article" style="background-image: url(<?php echo $background_image; ?>);">
                        <a itemprop="url" href="<?php $this->permalink() ?>"><span class="article-title"><?php $this->title() ?></span></a>
                        <span class="article-date"><?php $this->date('Y-m-d'); ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center;font-size: 20px;line-height: normal;padding-bottom: 20px;">没有找到文章</p>
                <div class="pagination">
                    <div class="next"><a class="next" title="" href="#" onclick="return history.back();">返回首页</a></div>
                </div>
            <?php endif; ?>
        </div>
        <div class="pagination">
            <?php if ($this->pageLink('上一页', 'prev')) { ?>
                <button id="prev-page"><?php $this->pageLink('上一页', 'prev'); ?></button>
            <?php } ?>
            <?php if ($this->pageLink('下一页', 'next')) { ?>
                <button id="next-page"><?php $this->pageLink('下一页', 'next'); ?></button>
            <?php } ?>
        </div>
    </div>
    <?php $this->need('page/footer.php'); ?>