<?php
// admin.php - 后台（JSON 存储）
// 已合并 admin.css 内容到 assets/style.css，所以这里只加载 assets/style.css
session_start();
$dataFile = __DIR__ . '/data/services.json';
if (!file_exists($dataFile)) {
    if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0755, true);
    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// 修改后的 load_dotenv 函数，不使用 putenv()
function load_dotenv($path) {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!strpos($line, '=')) continue;
        [$key, $val] = array_map('trim', explode('=', $line, 2));
        $val = preg_replace('/^([\'"])(.*)\\1$/', '$2', $val);
        // 不再使用 putenv，直接设置到 $_ENV 和 $_SERVER
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
}

// 获取 ADMIN_PASS
$ADMIN_PASS = 'changeme'; // 默认值
$envPath = __DIR__ . '/.env';

if (file_exists($envPath)) {
    load_dotenv($envPath);
    // 从 $_ENV 或 $_SERVER 读取
    if (!empty($_ENV['ADMIN_PASS'])) {
        $ADMIN_PASS = $_ENV['ADMIN_PASS'];
    } elseif (!empty($_SERVER['ADMIN_PASS'])) {
        $ADMIN_PASS = $_SERVER['ADMIN_PASS'];
    }
}

// 检查是否有环境变量设置（通过Web服务器配置）
if (getenv('ADMIN_PASS') !== false) {
    $ADMIN_PASS = getenv('ADMIN_PASS');
}

// 登录处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $err = "密码错误";
    }
}

if (!empty($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (!isset($_SESSION['admin'])) {
    // 登录表单
    ?>
    <!doctype html>
    <html lang="zh-CN">
    <head><meta charset="utf-8"><title>管理登录</title><link rel="stylesheet" href="assets/style.css"></head>
    <body class="admin-login">
      <div class="login-box">
        <h2>后台登录</h2>
        <?php if (!empty($err)) echo "<p class='error'>".htmlspecialchars($err)."</p>"; ?>
        <form method="post">
          <input type="hidden" name="action" value="login">
          <div><input type="password" name="password" placeholder="请输入管理员密码"></div>
          <div><button type="submit">登录</button></div>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// 已登录，读取 services
$raw = file_get_contents($dataFile);
$services = json_decode($raw, true);
if (!is_array($services)) $services = [];
usort($services, function($a,$b){
    $ha = ($a['show_home'] ?? 0) ? 1 : 0;
    $hb = ($b['show_home'] ?? 0) ? 1 : 0;
    if ($ha !== $hb) return $hb <=> $ha;

    $sa = $a['sort_order'] ?? 0; $sb = $b['sort_order'] ?? 0;
    if ($sa === $sb) return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
    return $sa <=> $sb;
});
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>导航管理</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header class="topbar admin-top">
    <div class="brand"><h1>导航管理</h1></div>
    <div><a href="index.php" target="_blank">查看前台</a> | <a href="?logout=1">登出</a></div>
  </header>

  <main class="container admin">
    <section class="admin-actions">
      <div class="admin-toolbar">
        <div class="admin-toolbar-left">
          <button id="btn-new" class="primary">新增服务</button>
          <button id="save-order-btn">保存布局</button>
        </div>
        <div class="admin-toolbar-right">
          <input id="admin-search" class="admin-search" type="search" placeholder="搜索服务…" autocomplete="off">
          <select id="admin-page-size" class="admin-page-size">
            <option value="10">10/页</option>
            <option value="20" selected>20/页</option>
            <option value="50">50/页</option>
            <option value="100">100/页</option>
          </select>
        </div>
      </div>
      <p class="muted">注：拖拽表格行改变排序,点击状态可切换启用/停用</p>
    </section>

    <section>
      <table class="admin-table">
        <thead><tr>
          <th>ID</th>
          <th>图标</th>
          <th>名称</th>
          <th>描述</th>
          <th>v6</th>
          <th>v4</th>
          <th>局域网</th>
          <th>首页显示</th>
          <th>状态</th>
          <th>操作</th>
        </tr></thead>
        <tbody id="svc-list">
          <?php foreach($services as $s): ?>
            <tr data-id="<?=htmlspecialchars($s['id'])?>" draggable="true">
              <td><?=htmlspecialchars($s['id'])?></td>
              <td><img src="<?=htmlspecialchars($s['icon'] ?: 'favicon.ico')?>" class="thumb" id="icon-<?=htmlspecialchars($s['id'])?>"></td>
              <td><?=htmlspecialchars($s['name'])?></td>
              <td><?=htmlspecialchars($s['description'])?></td>
              <td class="mono"><?=htmlspecialchars($s['link_v6'])?></td>
              <td class="mono"><?=htmlspecialchars($s['link_v4'])?></td>
              <td class="mono"><?=htmlspecialchars($s['link_lan'])?></td>
              <td>
                <input
                  type="checkbox"
                  class="home-checkbox"
                  data-id="<?=htmlspecialchars($s['id'])?>"
                  <?=((($s['show_home'] ?? 0) == 1) ? 'checked' : '')?>
                >
              </td>
              <td>
                <?php if (!empty($s['enabled'])): ?>
                  <span class="status-badge enabled" data-id="<?=htmlspecialchars($s['id'])?>">启用</span>
                <?php else: ?>
                  <span class="status-badge disabled" data-id="<?=htmlspecialchars($s['id'])?>">禁用</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="action-group">
                  <button class="edit-btn" data-id="<?=htmlspecialchars($s['id'])?>">编辑</button>
                  <button class="del-btn" data-id="<?=htmlspecialchars($s['id'])?>">删除</button>
                  <button class="fetch-icon-btn" data-id="<?=htmlspecialchars($s['id'])?>">获取图标</button>
                  <button class="upload-icon-btn" data-id="<?=htmlspecialchars($s['id'])?>">上传图标</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
    <section class="admin-pagination-wrap">
      <div class="admin-pagination">
        <button id="admin-prev" class="admin-page-btn">上一页</button>
        <span id="admin-page-info" class="admin-page-info"></span>
        <button id="admin-next" class="admin-page-btn">下一页</button>
      </div>
    </section>
  </main>

  <!-- Modal overlay & modal（overlay 默认隐藏，通过 .show 控制显示） -->
  <div id="modal-overlay" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <header class="modal-header">
        <h3 id="modal-title">新增服务</h3>
        <button id="modal-close" class="modal-close" aria-label="关闭">&times;</button>
      </header>
      <div class="modal-body">
        <form id="svc-form" enctype="multipart/form-data">
          <input type="hidden" name="id" value="">
          <div class="form-row"><label>名称 <input name="name" required></label></div>
          <div class="form-row"><label>描述 <input name="description"></label></div>

          <!-- 注意：图标文件输入已移出弹窗（改为行内上传按钮） -->

          <div class="form-row"><label>v6 链接 <input name="link_v6"></label></div>
          <div class="form-row"><label>v4 链接 <input name="link_v4"></label></div>
          <div class="form-row"><label>局域网 链接 <input name="link_lan"></label></div>

          <div class="form-row"><label style="display: block;">启用 <input name="enabled" type="checkbox" checked></label></div>
          <div class="form-row"><label style="display: block;">首页显示 <input name="show_home" type="checkbox" checked></label></div>

          <div class="form-actions">
            <button type="submit" class="primary">保存</button>
            <button type="button" id="modal-reset">重置</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="assets/app.js"></script>
  <script>
    // 后台 AJAX 状态切换由 assets/app.js 处理
  </script>
</body>
</html>
