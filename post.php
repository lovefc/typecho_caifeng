<?php $this->need('page/header.php'); ?>
<?php trackReferer(); ?>
<?php if ($this->options->codeTheme): ?>
  <link href="<?php $this->options->codeTheme(); ?>" rel="stylesheet">
<?php else: ?>
  <link href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css" rel="stylesheet">
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<style>
  pre code.hljs {
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-x: auto;
  }
  @media (max-width: 768px) {
    pre code.hljs {
      white-space: pre-wrap;
      word-break: break-all;
    }
  }
</style>

<body>
  <!-- 内容页 -->
  <div class="content-page" id="content-page">
    <div class="container">
      <div class="title"><?php $this->title() ?>
      </div>
      <div class="content-detail">
        <?php $this->content(); ?>
      </div>
      <div class="author-info">
        <p><span style="color:rgba(0, 0, 0, .3);"><?php _e('发布时间：'); ?><?php $this->date('Y-m-d H:i:s'); ?></span></p>
      </div>
      <?php
      $currentPage = max(1, (int)$this->request->get('page', 1));
      $commentsData = getCurrentPostComments($currentPage, 5, function ($comment) {
        $options = Typecho_Widget::widget('Widget_Options');
        $timezone = $options->timezone;  
        $comment['time'] = date('Y-m-d H:i:s', $comment['created']+$timezone);
        $comment['url'] = $comment['url'] ? htmlspecialchars($comment['url']) : '#';
        return $comment;
      });
      ?>

      <!-- 评论列表 -->
      <div class="comment-list">
        <?php foreach ($commentsData['comments'] as $comment): ?>
          <div class="comment-item">
            <div class="avatar">
              <a href="<?php echo $comment['url']; ?>" target="_blank" rel="noopener noreferrer"/><img src="<?php echo $comment['avatar']; ?>" alt="<?php echo $comment['author']; ?>"></a>
            </div>
            <div class="comment-content">
              <div class="user-info">
                <span class="nickname" <?php if (isMainAccountLoggedIn()): ?>onclick="chang(<?php echo $comment['coid']; ?>,'<?php echo $comment['author']; ?>')" <?php endif; ?>><?php echo $comment['author']; ?></span>
                <span class="comment-time"><?php echo $comment['time']; ?></span>
              </div>
              <div class="comment-text"><?php if ($comment['aite_author']): ?><span class="aite">@<?php echo $comment['aite_author']; ?></span><?php endif; ?><?php echo $comment['content']; ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- 分页导航 -->
      <?php if ($commentsData['pagination']['totalPages'] > 1): ?>
        <nav class="pagination">
          <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo ($currentPage - 1); ?>#comments" class="prev">上一页</a>
          <?php endif; ?>


          <?php if ($currentPage < $commentsData['pagination']['totalPages']): ?>
            <a href="?page=<?php echo ($currentPage + 1); ?>#comments" class="next">下一页</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>


      <!-- 评论表单 -->
      <div class="comment-section">
        <h3>欢迎留言</h3>
        <form class="comment-form" method="post" action="<?php $this->commentUrl() ?>" id="comment-form">
          <?php if ($this->user->hasLogin()): ?>
            <p><?php _e('登录身份: '); ?><a
                href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a>. <a
                href="<?php $this->options->logoutUrl(); ?>" title="Logout"><?php _e('退出'); ?> &raquo;</a>
            </p>
          <?php else: ?>
            <div class="form-row">
              <div class="form-group">
                <input
                  type="text" name="author" id="author"
                  placeholder="昵称（必填）"
                  required
                  class="form-input" value="<?php $this->remember('author'); ?>" />
              </div>
              <div class="form-group">
                <input
                  type="email" name="mail" id="mail"
                  placeholder="邮件地址（不公开）"
                  class="form-input" value="<?php $this->remember('mail'); ?>" />
              </div>
              <div class="form-group">
                <input
                  <input type="url" name="url" id="url" placeholder="<?php _e('http://'); ?>"
                  value="<?php $this->remember('url'); ?>"
                  placeholder="https://"
                  class="form-input" <?php if ($this->options->commentsRequireURL): ?> required<?php endif; ?> />
              </div>
            </div>
          <?php endif; ?>
          <div class="form-group" id="<?php $this->respondId(); ?>">
            <textarea
              placeholder="请输入评论内容..."
              rows="4"
              required name="text" id="textarea"
              class="form-input"></textarea>
          </div>
          <input type="hidden" name="parent" id="parent" value="">
          <button type="submit" class="submit-btn">提交评论</button>
        </form>
      </div>

      <?php
      $safeBackUrl = isset($_SESSION['typecho_referer'])
        && strpos($_SESSION['typecho_referer'], Helper::options()->siteUrl) === 0
        ? $_SESSION['typecho_referer']
        : Helper::options()->siteUrl;
      ?>
      <a href="<?php echo $safeBackUrl; ?>" class="back-button" data-fallback="<?= Helper::options()->siteUrl ?>" onclick="return handleBack(event)">返回上一页</a>
      <a href="<?= Helper::options()->siteUrl ?>" class="back-button">返回首页</a>
    </div>
  </div>

  <script>
    <?php if (isMainAccountLoggedIn()): ?>
      function chang(id, name) {
        // 获取元素
        let textarea = document.getElementById('textarea');
        // 修改 textarea 的值
        textarea.placeholder = '回复【' + name + '】';
        let parent = document.getElementById('parent');
        parent.value = id;
      };
    <?php endif; ?>

    function handleBack(e) {
      e.preventDefault();
      const target = e.currentTarget;
      if (document.referrer.includes(window.location.host)) {
        window.history.back();
      } else {
        window.location.href = target.dataset.fallback;
      }
      sessionStorage.removeItem('commentSubmitted');
    }

    document.addEventListener('DOMContentLoaded', function() {
      if (performance.navigation.type === 1) {
        if (sessionStorage.getItem('commentSubmitted')) {
          sessionStorage.removeItem('commentSubmitted');
          window.history.replaceState(null, '', window.location.href.split('#')[0]);
        }
      }
    });

    document.querySelector('#comment-form').addEventListener('submit', function() {
      sessionStorage.setItem('commentSubmitted', 'true');
    });
  </script>
  <script>
    // 等页面加载完成后初始化代码高亮
    document.addEventListener('DOMContentLoaded', (event) => {
      hljs.highlightAll();
    });
  </script>
  <script type="text/javascript">
    (function() {
      var event = document.addEventListener ? {
          add: 'addEventListener',
          triggers: ['scroll', 'mousemove', 'keyup', 'touchstart'],
          load: 'DOMContentLoaded'
        } : {
          add: 'attachEvent',
          triggers: ['onfocus', 'onmousemove', 'onkeyup', 'ontouchstart'],
          load: 'onload'
        },
        added = false;

      document[event.add](event.load, function() {
        var r = document.getElementById('<?php $this->respondId(); ?>'),
          input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_';
        input.value = <?php echo \Typecho\Common::shuffleScriptVar($this->security->getToken($this->request->getRequestUrl())); ?>

        let _form = document.getElementById('comment-form');
        _form.appendChild(input);
      });
    })();
  </script>

  <?php $this->need('page/footer.php'); ?>