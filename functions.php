<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once __DIR__ . '/core/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/core/PHPMailer/SMTP.php';
require_once __DIR__ . '/core/PHPMailer/Exception.php';

/**
 * 获取分类列表
 * 
 * @param array $params 查询参数
 * @return array 分类数据数组
 */
function getCategories($params = array())
{
    $defaults = array(
        'ignoreEmpty' => true,   // 是否忽略空分类
        'current'     => '',     // 当前分类ID
        'order'       => 'mid',  // 排序字段 (mid/name/slug/count)
        'sort'        => 'ASC',  // 排序方式
        'showCount'   => true,   // 是否显示文章数
        'showAll'     => false   // 是否显示所有子分类
    );

    // 合并参数
    $options = array_merge($defaults, $params);

    // 初始化分类组件
    $categories = Typecho_Widget::widget('Widget_Metas_Category_List', $options);

    $result = array();
    while ($categories->next()) {
        $category = array(
            'id'          => $categories->mid,
            'name'        => $categories->name,
            'slug'        => $categories->slug,
            'description' => $categories->description,
            'parent'      => $categories->parent,
            'count'       => $categories->count,
            'permalink'   => $categories->permalink,
            'children'    => array() // 用于存储子分类
        );

        // 如果需要显示所有子分类
        if ($options['showAll'] && $category['parent'] > 0) {
            $parentId = $category['parent'];
            if (isset($result[$parentId])) {
                $result[$parentId]['children'][] = $category;
            }
        } else {
            $result[$categories->mid] = $category;
        }
    }
    return $result;
}

/**
 * 获取当前文章分页评论
 * @param int $currentPage 当前页码
 * @param int $pageSize 每页数量
 * @param callable|null $callback 评论处理回调
 * @return array
 */
function getCurrentPostComments($currentPage = 1, $pageSize = 10, $callback = null)
{
    $archive = Typecho_Widget::widget('Widget_Archive');
    $db = Typecho_Db::get();
    $options = Typecho_Widget::widget('Widget_Options');
    $currentPage = max(1, (int)$currentPage);
    $pageSize = max(1, (int)$pageSize);
    $cid = (int)$archive->cid;
    $countQuery = $db->select('COUNT(coid) AS num')
        ->from('table.comments')
        ->where('cid = ?', $cid)
        ->where('status = ?', 'approved');

    $total = $db->fetchObject($countQuery)->num;
    // 主评论查询
    $mainQuery = $db->select()
        ->from('table.comments')
        ->where('cid = ?', $cid)
        ->where('status = ?', 'approved')
        ->order('coid', Typecho_Db::SORT_DESC)
        ->page($currentPage, $pageSize);
    $comments = $db->fetchAll($mainQuery);
    $result = [
        'comments' => [],
        'pagination' => [
            'total' => $total,
            'currentPage' => $currentPage,
            'totalPages' => $pageSize > 0 ? ceil($total / $pageSize) : 0,
            'pageSize' => $pageSize
        ]
    ];
    foreach ($comments as $comment) {
        $aite_author = null;
        if ($comment['parent'] != 0) {
            $coid = $comment['parent'];
            $query = $db->select()
                ->from('table.comments')
                ->where('coid = ?', $coid)
                ->where('status = ?', 'approved')->limit(1);
            $children = $db->fetchRow($query);
            $aite_author = $children['author'];
        }
        $item = [
            'coid' => $comment['coid'],
            'author' => $comment['author'],
            'aite_author' => $aite_author,
            'mail' => $comment['mail'],
            'url' => $comment['url'],
            'content' => $comment['text'],
            'created' => $comment['created'],
            'parent' => $comment['parent'],
            'avatar' => gravatarUrl(
                $comment['mail'],
                48,
                $options->commentsAvatarRating,
                null,
                Typecho_Request::getInstance()->isSecure()
            )
        ];
        if (is_callable($callback)) {
            $item = $callback($item);
        }

        $result['comments'][] = $item;
    }

    return $result;
}

// 判断是不是主账户
function isMainAccountLoggedIn()
{
    $user = Typecho_Widget::widget('Widget_User');
    if ($user->hasLogin() && $user->pass('administrator', true)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取gravatar头像地址
 *
 * @param string|null $mail
 * @param int $size
 * @param string|null $rating
 * @param string|null $default
 * @param bool $isSecure
 *
 * @return string
 */
function gravatarUrl(
    ?string $mail,
    int $size,
    ?string $rating = null,
    ?string $default = null,
    bool $isSecure = true
): string {
    if (defined('__TYPECHO_GRAVATAR_PREFIX__')) {
        $url = __TYPECHO_GRAVATAR_PREFIX__;
    } else {
        $url = $isSecure ? 'https://cravatar.cn' : 'https://cravatar.cn';
        $url .= '/avatar/';
    }

    if (!empty($mail)) {
        $url .= md5(strtolower(trim($mail)));
    }

    $url .= '?s=' . $size;

    if (isset($rating)) {
        $url .= '&amp;r=' . $rating;
    }

    if (isset($default)) {
        $url .= '&amp;d=' . $default;
    }

    return $url;
}

/**
 * 从 Markdown 内容中提取所有图片地址
 * @param string $markdown Markdown原始内容
 * @return array 图片地址数组
 */
function extractMarkdownImages($markdown)
{
    $images = array();

    // 匹配行内格式图片
    preg_match_all('/!\[.*?\]\((.*?)(?:\s+"[^"]*")?\)/', $markdown, $inlineMatches);
    if (!empty($inlineMatches[1])) {
        $images = array_merge($images, $inlineMatches[1]);
    }

    // 匹配参考式图片
    preg_match_all('/\[.*?\]:\s*(\S+)(?:\s+"[^"]*")?/', $markdown, $refMatches);
    if (!empty($refMatches[1])) {
        $images = array_merge($images, $refMatches[1]);
    }
    $images = array_unique(array_filter($images));
    return $images[0] ?? null;
}

/**
 * 提取HTML中第一个<img>标签的src链接
 * @param string $html 输入的HTML内容
 * @return string|false 成功返回src值，失败返回false
 */
function getFirstImageSrc($html)
{
    // 方式1：使用DOMDocument（推荐）
    $dom = new DOMDocument();
    @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $images = $dom->getElementsByTagName('img');
    if ($images->length > 0) {
        return $images->item(0)->getAttribute('src');
    }

    // 方式2：使用正则表达式（备用方案）
    preg_match('/<img[^>]+src=["\']([^"\'>]+)["\']/i', $html, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

// 主题配置
function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('主题头像'),
        _t('主题头像')
    );
    $form->addInput($logoUrl);
    $detail = new \Typecho\Widget\Helper\Form\Element\Text(
        'detail',
        null,
        null,
        _t('个人描述'),
        _t('个人描述，非必填。')
    );
    $form->addInput($detail);
    $background = new \Typecho\Widget\Helper\Form\Element\Text(
        'background',
        null,
        null,
        _t('背景图片'),
        _t('网站的背景图片，不填写时显示默认颜色，非必填。')
    );
    $form->addInput($background);
    $fontcdn = new \Typecho\Widget\Helper\Form\Element\Text(
        'fontcdn',
        null,
        null,
        _t('字体cdn'),
        _t('网站字体的cdn-<a href="https://chinese-font.netlify.app/zh-cn/cdn/"  target="_blank" rel="noopener noreferrer">中文字体库</a>')
    );
    $form->addInput($fontcdn);
    $fontname = new \Typecho\Widget\Helper\Form\Element\Text(
        'fontname',
        null,
        null,
        _t('字体名称'),
        _t('网站字体的名称，要跟cdn搭配')
    );
    $form->addInput($fontname);
    $codeTheme = new Typecho_Widget_Helper_Form_Element_Select(
        'codeTheme',
        array(
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css' => 'default(默认)',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/brown-paper.min.css' => 'brown-paper',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/dark.min.css' => 'dark',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/docco.min.css' => 'docco',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css' => 'github',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/idea.min.css' => 'idea',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/lightfair.min.css' => 'lightfair',
            '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/monokai.min.css' => 'monokai',
        ),
        '//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css',
        '代码高亮样式',
        ''
    );
    $form->addInput($codeTheme->multiMode());
    $mailNotification = new Typecho_Widget_Helper_Form_Element_Radio(
        'mailNotification',
        array(
            '1' => '启用',
            '0' => '禁用'
        ),
        '0', // 默认值
        '是否启用邮件通知功能',
        '请选择是否启用该功能'
    );
    $form->addInput($mailNotification);
    $emailHost = new \Typecho\Widget\Helper\Form\Element\Text(
        'emailHost',
        null,
        null,
        _t('邮件服务器地址'),
        _t('例如：smtp.qq.com')
    );
    $form->addInput($emailHost);
    $emailSecure = new Typecho_Widget_Helper_Form_Element_Radio(
        'emailSecure',
        array(
            'none' => '无加密',
            'auto' => '自动',
            'ssl' => 'ssl',
            'tls' => 'tls',
            'starttls' => 'starttls'
        ),
        'ssl', // 默认值
        '邮箱加密方式',
        ''
    );
    $form->addInput($emailSecure);
    $emailPort = new \Typecho\Widget\Helper\Form\Element\Text(
        'emailPort',
        null,
        null,
        _t('邮件服务器端口'),
        _t('根据加密方式：tls|587，ssl|465，starttls|587')
    );
    $form->addInput($emailPort);
    $emailUsername = new \Typecho\Widget\Helper\Form\Element\Text(
        'emailUsername',
        null,
        null,
        _t('邮箱用户名'),
        _t('邮箱用户名,例如admin@lovefc.cn')
    );
    $form->addInput($emailUsername);
    $emailPassword = new \Typecho\Widget\Helper\Form\Element\Text(
        'emailPassword',
        null,
        null,
        _t('邮箱密码'),
        _t('现在很多都是授权码，请注意填写')
    );
    $form->addInput($emailPassword);
    $emailAddress = new \Typecho\Widget\Helper\Form\Element\Text(
        'emailAddress',
        null,
        null,
        _t('接受邮件的邮箱'),
        _t('<button type="button" class="btn primary" id="testButton">测试发送</button>')
    );
    $form->addInput($emailAddress);
    configFooter();
}

// 处理按钮点击事件
if (isset($_POST['testButton'])) {
    sendTestEmail();
    exit;
}

// 配置的js部分
function configFooter()
{
    echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var testButton = document.getElementById("testButton");
        if (testButton) {
            testButton.addEventListener("click", function() {
				const input = document.querySelector(\'input[name="emailAddress"]\');
				var testEmail = input.value;
                var formData = new FormData();
                formData.append("testButton", "1");
                formData.append("testEmail", testEmail);

                fetch(window.location.href, {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert("请注意查收邮箱，未收到则为发送失败，请检查设置");
                })
                .catch(error => {
                    console.error("Error:", error);
                });
            });
        }
    });
    </script>
    ';
}

// 测试发送邮件
function sendTestEmail()
{
    $testEmail = Helper::options()->emailAddress;
    $host = Helper::options()->emailHost;
    $username = Helper::options()->emailUsername;
    $password = Helper::options()->emailPassword;
    $port = Helper::options()->emailPort;
    $secure = Helper::options()->emailSecure;
    if (empty($testEmail)) {
        echo '请输入要发送的邮件地址';
        return;
    }
    $subject = '测试邮件';
    $message = '这是一封测试邮件，用于验证邮件发送功能是否正常。';
    $title = $post->title;
    $time = date("Y-m-d H:i:s");
    $siteName = Helper::options()->title;
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $secure;
        $mail->Port = $port;
        $mailurl = $testEmail;
        $mail->setFrom($username, $siteName);
        $mail->addAddress($mailurl);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
    }
}

// 记录访问历史路径
function trackReferer()
{
    $currentUrl = Typecho_Request::getInstance()->getRequestUrl();
    $referer = Typecho_Request::getInstance()->getReferer();
    $siteurl =  Helper::options()->siteUrl;
    // 仅记录站内路径
    if ($referer && strpos($referer, $siteurl) === 0) {
        $_SESSION['typecho_referer'] = $referer;
    } else if (!isset($_SESSION['typecho_referer'])) {
        $_SESSION['typecho_referer'] = $siteurl;
    }
    // 当前URL不存入历史
    if ($currentUrl != $_SESSION['typecho_referer']) {
        $_SESSION['typecho_history'][] = $currentUrl;
        $_SESSION['typecho_history'] = array_slice(
            $_SESSION['typecho_history'],
            -5,
            5
        );
    }
}

// 邮件发送
function sendMail($comment, $post)
{
    $title = $post->title;
    $text = $comment['text'] ?? null;
    $author = $comment['author'] ?? null;
	$options = Typecho_Widget::widget('Widget_Options');
    $timezone = $options->timezone;
    $time = date('Y-m-d H:i:s', time()+$timezone);
    $postLink = $post->permalink ?? null;
    $blogLink = Helper::options()->siteUrl;
    $siteName = Helper::options()->title;
    $tomail = Helper::options()->emailAddress;
    $host = Helper::options()->emailHost;
    $username = Helper::options()->emailUsername;
    $password = Helper::options()->emailPassword;
    $port = Helper::options()->emailPort;
    $secure = Helper::options()->emailSecure;
    $parent = $comment['parent'] ?? 0;
    $template = file_get_contents(__DIR__ . '/page/comment.html');
    $template = str_replace(
        ['{{commentAuthor}}', '{{commentContent}}', '{{commentTime}}', '{{postTitle}}', '{{postLink}}', '{{blogLink}}'],
        [$author, $text, $time, $title, $postLink, $blogLink],
        $template
    );
    $subject = "{$author}给您发来了一条新评论";
    // 如果是回复
    if ($parent != 0) {
        $coid = $parent;
        $db = Typecho_Db::get();
        $query = $db->select()
            ->from('table.comments')
            ->where('coid = ?', $coid)->limit(1);
        $children = $db->fetchRow($query);
        $tomail = $children['mail'];

        $template = file_get_contents(__DIR__ . '/page/aite.html');
        $template = str_replace(
            ['{{commentContent}}', '{{commentTime}}', '{{postTitle}}', '{{postLink}}'],
            [$text, $time, $title, $postLink],
            $template
        );
        $subject = "您有一条新的回复";
    }
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $secure;
        $mail->Port = $port;
        $mail->setFrom($username, $siteName);
        $mail->addAddress($tomail);
        $mail->Subject = $subject;
        $mail->Body = $template;
        $mail->IsHTML(true);
        $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
    }
    return $comment;
}

// 截取字符串
function splitChineseFirst($str, $num = 100)
{
    preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $str, $matches);
    $chineseArray = $matches[0];
    $first100 = array_slice($chineseArray, 0, $num);
    return implode('', $first100);
}

// 输出header头
function echoHeader()
{
    $archive = Typecho_Widget::widget('Widget_Archive');
    $description = $archive->is('post') ? trim(splitChineseFirst((string)$archive->content), $num = 100) . '...' : Helper::options()->description;
    $key = '';
    if (count($archive->tags) > 0) {
        foreach ($archive->tags as $v) {
            $key .= $v['name'];
        }
    }
    $keywords = $archive->is('post') ? $key : Helper::options()->keywords;
    $allows = [
        'description'  => htmlspecialchars($description),
        'keywords'     => htmlspecialchars($keywords),
        'generator'    => 'lovefc-typecho 1.2.1',
        'template'     => Helper::options()->theme,
        'pingback'     => Helper::options()->xmlRpcUrl,
        'xmlrpc'       => Helper::options()->xmlRpcUrl . '?rsd',
        'wlw'          => Helper::options()->xmlRpcUrl . '?wlw',
        'rss2'         => Helper::options()->feedUrl,
        'rss1'         => Helper::options()->feedRssUrl,
        'commentReply' => 1,
        'antiSpam'     => 1,
        'atom'         => Helper::options()->feedAtomUrl
    ];
    $title = $archive->is('post') ? $archive->title . ' - ' . Helper::options()->title : Helper::options()->title;
    $allowFeed = true;
    $header = '';
    if (!empty($allows['description'])) {
        $header .= '<meta name="description" content="' . $allows['description'] . '" />' . "\n";
    }

    if (!empty($allows['keywords'])) {
        $header .= '<meta name="keywords" content="' . $allows['keywords'] . '" />' . "\n";
    }

    if (!empty($allows['generator'])) {
        $header .= '<meta name="generator" content="' . $allows['generator'] . '" />' . "\n";
    }

    if (!empty($allows['template'])) {
        $header .= '<meta name="template" content="' . $allows['template'] . '" />' . "\n";
    }

    if (!empty($allows['pingback']) && 2 == Helper::options()->allowXmlRpc) {
        $header .= '<link rel="pingback" href="' . $allows['pingback'] . '" />' . "\n";
    }

    if (!empty($allows['xmlrpc']) && 0 < Helper::options()->allowXmlRpc) {
        $header .= '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'
            . $allows['xmlrpc'] . '" />' . "\n";
    }

    if (!empty($allows['wlw']) && 0 < Helper::options()->allowXmlRpc) {
        $header .= '<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="'
            . $allows['wlw'] . '" />' . "\n";
    }

    if (!empty($allows['rss2']) && $allowFeed) {
        $header .= '<link rel="alternate" type="application/rss+xml" title="'
            . $title . ' &raquo; RSS 2.0" href="' . $allows['rss2'] . '" />' . "\n";
    }

    if (!empty($allows['rss1']) && $allowFeed) {
        $header .= '<link rel="alternate" type="application/rdf+xml" title="'
            . $title . ' &raquo; RSS 1.0" href="' . $allows['rss1'] . '" />' . "\n";
    }

    if (!empty($allows['atom']) && $allowFeed) {
        $header .= '<link rel="alternate" type="application/atom+xml" title="'
            . $title . ' &raquo; ATOM 1.0" href="' . $allows['atom'] . '" />' . "\n";
    }
    echo $header;
}

// 在主题初始化时执行
function themeInit()
{
    $mailNotification = Helper::options()->mailNotification;
    if ($mailNotification === '1') {
        Typecho_Plugin::factory('Widget_Feedback')->comment = 'sendMail';
    }
}
