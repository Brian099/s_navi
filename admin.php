<?php
// admin.php - 后台（JSON 存储）
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
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
}

// 获取 ADMIN_PASS 和 SERVER_NAME
$ADMIN_PASS = 'changeme';
$serverName = 'Simple-Nav';
$envPath = __DIR__ . '/.env';

if (file_exists($envPath)) {
    load_dotenv($envPath);
    if (!empty($_ENV['ADMIN_PASS'])) {
        $ADMIN_PASS = $_ENV['ADMIN_PASS'];
    } elseif (!empty($_SERVER['ADMIN_PASS'])) {
        $ADMIN_PASS = $_SERVER['ADMIN_PASS'];
    }

    if (!empty($_ENV['NAV_SERVER_NAME'])) {
        $serverName = $_ENV['NAV_SERVER_NAME'];
    } elseif (!empty($_ENV['SERVER_NAME'])) {
        $serverName = $_ENV['SERVER_NAME'];
    }
}

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
        $err = "密码错误，请重新输入";
    }
}

if (!empty($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (!isset($_SESSION['admin'])) {
    // 现代化管理登录界面
    ?>
    <!doctype html>
    <html lang="zh-CN">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>后台登录 - <?=htmlspecialchars($serverName)?></title>
      <link rel="stylesheet" href="assets/admin.css?v=<?=filemtime(__DIR__.'/assets/admin.css')?>">
    </head>
    <body class="admin-login-page">
      <div class="bg-glow"></div>
      <div class="login-card">
        <div class="login-brand">
          <div class="login-brand-icon">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <h2>管理控制台</h2>
          <p class="login-sub"><?=htmlspecialchars($serverName)?> • 身份验证</p>
        </div>

        <?php if (!empty($err)): ?>
          <div class="login-alert error">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?=htmlspecialchars($err)?></span>
          </div>
        <?php endif; ?>

        <form method="post" class="login-form">
          <input type="hidden" name="action" value="login">
          <div class="form-group">
            <label for="admin-pwd">管理员密码</label>
            <div class="input-with-icon">
              <svg class="field-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 2l-2 2m-1.5 1.5L16 7m-1.5 1.5L13 10m-1.5 1.5L10 13l-4 4-4 2 2-4 4-4 1.5-1.5M17 7l4 4"></path>
              </svg>
              <input type="password" id="admin-pwd" name="password" placeholder="请输入管理员密码" autofocus required autocomplete="current-password">
              <button type="button" class="btn-toggle-pwd" id="toggle-pwd-btn" aria-label="显示/隐藏密码">
                <svg class="icon-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>
          <button type="submit" class="btn-login-submit">
            <span>安全登录</span>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
        </form>

        <div class="login-footer">
          <a href="index.php" class="back-home-link">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="19" y1="12" x2="5" y2="12"></line>
              <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>返回前台导航</span>
          </a>
        </div>
      </div>
      <script>
        const pwdInput = document.getElementById('admin-pwd');
        const toggleBtn = document.getElementById('toggle-pwd-btn');
        if (pwdInput && toggleBtn) {
          toggleBtn.addEventListener('click', () => {
            const isPwd = pwdInput.type === 'password';
            pwdInput.type = isPwd ? 'text' : 'password';
            toggleBtn.style.opacity = isPwd ? '1' : '0.6';
          });
        }
      </script>
    </body>
    </html>
    <?php
    exit;
}

// 已登录，读取 services
$raw = file_get_contents($dataFile);
$services = json_decode($raw, true);
if (!is_array($services)) $services = [];

// 统计数据
$totalCount = count($services);
$enabledCount = 0;
$homeCount = 0;
$disabledCount = 0;

foreach ($services as $s) {
    if (!empty($s['enabled'])) {
        $enabledCount++;
    } else {
        $disabledCount++;
    }
    if (!empty($s['show_home'])) {
        $homeCount++;
    }
}

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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>导航服务管理 - <?=htmlspecialchars($serverName)?></title>
  <link rel="stylesheet" href="assets/admin.css?v=<?=filemtime(__DIR__.'/assets/admin.css')?>">
</head>
<body class="admin-body">
  <div class="bg-glow"></div>

  <!-- 顶部导航栏 -->
  <header class="admin-header">
    <div class="admin-header-inner">
      <div class="admin-brand">
        <div class="admin-brand-badge">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
          </svg>
        </div>
        <div>
          <h1 class="admin-title">导航管理控制台</h1>
          <span class="admin-subtitle"><?=htmlspecialchars($serverName)?></span>
        </div>
      </div>

      <div class="admin-header-actions">
        <a href="index.php" target="_blank" class="admin-nav-link" title="在前台查看">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
            <polyline points="15 3 21 3 21 9"></polyline>
            <line x1="10" y1="14" x2="21" y2="3"></line>
          </svg>
          <span>查看前台</span>
        </a>
        <a href="?logout=1" class="admin-nav-link logout" title="安全退出">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          <span>登出</span>
        </a>
      </div>
    </div>
  </header>

  <main class="admin-container">
    <!-- 数据概览统计看板 -->
    <section class="admin-stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">总服务数</span>
          <span class="stat-value" id="stat-total"><?=$totalCount?></span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon enabled">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">正常运行</span>
          <span class="stat-value text-success" id="stat-enabled"><?=$enabledCount?></span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon home">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">首页展示</span>
          <span class="stat-value text-primary" id="stat-home"><?=$homeCount?></span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon disabled">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">已停用</span>
          <span class="stat-value text-muted" id="stat-disabled"><?=$disabledCount?></span>
        </div>
      </div>
    </section>

    <!-- 管理操作工具栏 -->
    <section class="admin-panel">
      <div class="admin-toolbar-card">
        <div class="admin-toolbar-primary">
          <button id="btn-new" class="btn-action-primary">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>新增服务</span>
          </button>
          <button id="save-order-btn" class="btn-action-secondary">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
              <polyline points="17 21 17 13 7 13 7 21"></polyline>
              <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <span>保存布局与排序</span>
          </button>
        </div>

        <div class="admin-toolbar-filters">
          <div class="admin-search-wrap">
            <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input id="admin-search" class="admin-search-input" type="search" placeholder="搜索服务名称、描述或链接..." autocomplete="off">
            <span class="search-clear-btn" id="search-clear-btn" title="清空搜索">✕</span>
          </div>

          <div class="page-size-wrap">
            <label for="admin-page-size" class="page-size-label">每页</label>
            <select id="admin-page-size" class="admin-select">
              <option value="10">10 条</option>
              <option value="20" selected>20 条</option>
              <option value="50">50 条</option>
              <option value="100">100 条</option>
            </select>
          </div>
        </div>
      </div>

      <div class="admin-tips-bar">
        <div class="tips-item">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <span>按住行首手柄可上下拖拽排序，点击状态药丸可直接切换启用/停用</span>
        </div>
        <div id="layout-change-badge" class="layout-dirty-badge" style="display: none;">
          <span>● 排序或布局已更改，请点击保存</span>
        </div>
      </div>

      <!-- 表格视图 -->
      <div class="admin-table-container">
        <table class="admin-modern-table">
          <thead>
            <tr>
              <th class="col-drag" title="拖拽排序">排序</th>
              <th class="col-icon">图标</th>
              <th class="col-info">服务名称 / 描述</th>
              <th class="col-links">多线路地址 (v6 / v4 / 局域网)</th>
              <th class="col-home">首页展示</th>
              <th class="col-status">状态</th>
              <th class="col-actions">操作</th>
            </tr>
          </thead>
          <tbody id="svc-list">
            <?php foreach($services as $s): ?>
              <?php
                $iconSrc = 'favicon.ico';
                if (!empty($s['icon']) && (strpos($s['icon'],'http://') === 0 || strpos($s['icon'],'https://') === 0)) {
                    $iconSrc = $s['icon'];
                } elseif (!empty($s['icon']) && file_exists(__DIR__ . '/' . $s['icon'])) {
                    $iconSrc = $s['icon'];
                }
                $hasV6 = !empty($s['link_v6']);
                $hasV4 = !empty($s['link_v4']);
                $hasLan = !empty($s['link_lan']);
              ?>
              <tr data-id="<?=htmlspecialchars($s['id'])?>" draggable="true" class="svc-row">
                <td class="col-drag">
                  <div class="drag-handle" title="按住拖动排序">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                      <circle cx="9" cy="6" r="1.5"></circle>
                      <circle cx="15" cy="6" r="1.5"></circle>
                      <circle cx="9" cy="12" r="1.5"></circle>
                      <circle cx="15" cy="12" r="1.5"></circle>
                      <circle cx="9" cy="18" r="1.5"></circle>
                      <circle cx="15" cy="18" r="1.5"></circle>
                    </svg>
                    <span class="row-id">#<?=htmlspecialchars($s['id'])?></span>
                  </div>
                </td>
                <td class="col-icon">
                  <div class="table-icon-wrap">
                    <img src="<?=htmlspecialchars($iconSrc)?>" class="thumb" id="icon-<?=htmlspecialchars($s['id'])?>" alt="" onerror="this.src='favicon.ico'">
                  </div>
                </td>
                <td class="col-info">
                  <div class="service-name-box">
                    <span class="service-title"><?=htmlspecialchars($s['name'])?></span>
                    <?php if (!empty($s['description'])): ?>
                      <span class="service-desc" title="<?=htmlspecialchars($s['description'])?>"><?=htmlspecialchars($s['description'])?></span>
                    <?php else: ?>
                      <span class="service-desc muted">暂无描述</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="col-links">
                  <div class="network-badges">
                    <div class="net-chip <?=($hasV6 ? 'has-link' : 'empty')?>" title="<?=htmlspecialchars($s['link_v6'] ?? '未配置')?>">
                      <span class="net-label">v6</span>
                      <span class="net-val"><?=($hasV6 ? htmlspecialchars($s['link_v6']) : '—')?></span>
                    </div>
                    <div class="net-chip <?=($hasV4 ? 'has-link' : 'empty')?>" title="<?=htmlspecialchars($s['link_v4'] ?? '未配置')?>">
                      <span class="net-label">v4</span>
                      <span class="net-val"><?=($hasV4 ? htmlspecialchars($s['link_v4']) : '—')?></span>
                    </div>
                    <div class="net-chip <?=($hasLan ? 'has-link' : 'empty')?>" title="<?=htmlspecialchars($s['link_lan'] ?? '未配置')?>">
                      <span class="net-label">LAN</span>
                      <span class="net-val"><?=($hasLan ? htmlspecialchars($s['link_lan']) : '—')?></span>
                    </div>
                  </div>
                </td>
                <td class="col-home">
                  <label class="modern-switch" title="切换是否在首页卡片区显示">
                    <input
                      type="checkbox"
                      class="home-checkbox"
                      data-id="<?=htmlspecialchars($s['id'])?>"
                      <?=((($s['show_home'] ?? 0) == 1) ? 'checked' : '')?>
                    >
                    <span class="switch-slider"></span>
                  </label>
                </td>
                <td class="col-status">
                  <?php if (!empty($s['enabled'])): ?>
                    <button type="button" class="status-badge enabled" data-id="<?=htmlspecialchars($s['id'])?>" title="点击停用">
                      <span class="status-dot"></span>
                      <span>启用</span>
                    </button>
                  <?php else: ?>
                    <button type="button" class="status-badge disabled" data-id="<?=htmlspecialchars($s['id'])?>" title="点击启用">
                      <span class="status-dot"></span>
                      <span>禁用</span>
                    </button>
                  <?php endif; ?>
                </td>
                <td class="col-actions">
                  <div class="action-group">
                    <button type="button" class="btn-tbl edit-btn" data-id="<?=htmlspecialchars($s['id'])?>" title="编辑服务详情">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                      <span>编辑</span>
                    </button>
                    <button type="button" class="btn-tbl fetch-icon-btn" data-id="<?=htmlspecialchars($s['id'])?>" title="自动抓取网页 Favicon 图标">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                      </svg>
                      <span>抓取</span>
                    </button>
                    <button type="button" class="btn-tbl upload-icon-btn" data-id="<?=htmlspecialchars($s['id'])?>" title="上传自定义图标图片">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                      </svg>
                      <span>上传</span>
                    </button>
                    <button type="button" class="btn-tbl del-btn" data-id="<?=htmlspecialchars($s['id'])?>" title="删除该服务">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                      <span>删除</span>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- 分页栏 -->
      <div class="admin-pagination-wrap">
        <div class="admin-pagination">
          <button id="admin-prev" class="admin-page-btn">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>上一页</span>
          </button>
          <div id="admin-page-info" class="admin-page-info"></div>
          <button id="admin-next" class="admin-page-btn">
            <span>下一页</span>
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      </div>
    </section>
  </main>

  <!-- 编辑 / 新增 弹窗 -->
  <div id="modal-overlay" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <header class="modal-header">
        <div class="modal-title-wrap">
          <div class="modal-title-icon">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 20h9"></path>
              <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
          </div>
          <h3 id="modal-title">新增服务</h3>
        </div>
        <button id="modal-close" class="modal-close-btn" aria-label="关闭">&times;</button>
      </header>

      <div class="modal-body">
        <form id="svc-form" enctype="multipart/form-data">
          <input type="hidden" name="id" value="">

          <div class="form-section-title">基本信息</div>
          <div class="form-grid">
            <div class="form-item full">
              <label>服务名称 <span class="req">*</span></label>
              <input type="text" name="name" placeholder="例如: 路由器后台、NAS 存储、影视库" required autocomplete="off">
            </div>
            <div class="form-item full">
              <label>服务描述</label>
              <input type="text" name="description" placeholder="简短说明，例如: 家庭主路由管理界面" autocomplete="off">
            </div>
          </div>

          <div class="form-section-title">访问链接 (至少填写一项)</div>
          <div class="form-grid">
            <div class="form-item">
              <label>
                <span class="net-tag v6">IPv6</span> 链接
              </label>
              <input type="text" name="link_v6" placeholder="http://[2409:...]:8080" autocomplete="off">
            </div>
            <div class="form-item">
              <label>
                <span class="net-tag v4">IPv4</span> 链接
              </label>
              <input type="text" name="link_v4" placeholder="http://192.168.1.1:8080 或域名" autocomplete="off">
            </div>
            <div class="form-item full">
              <label>
                <span class="net-tag lan">局域网 LAN</span> 链接
              </label>
              <input type="text" name="link_lan" placeholder="http://10.0.0.2:80 或 http://nas.local" autocomplete="off">
            </div>
          </div>

          <div class="form-section-title">显示与状态配置</div>
          <div class="form-switch-row">
            <div class="switch-item">
              <div class="switch-info">
                <span class="switch-title">启用状态</span>
                <span class="switch-desc">是否在前台导航中可用</span>
              </div>
              <label class="modern-switch">
                <input name="enabled" type="checkbox" checked>
                <span class="switch-slider"></span>
              </label>
            </div>

            <div class="switch-item">
              <div class="switch-info">
                <span class="switch-title">首页展示</span>
                <span class="switch-desc">是否展示在前台首页九宫格卡片中</span>
              </div>
              <label class="modern-switch">
                <input name="show_home" type="checkbox" checked>
                <span class="switch-slider"></span>
              </label>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" id="modal-reset" class="btn-modal-secondary">重置表单</button>
            <button type="submit" class="btn-modal-primary">保存服务</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Toast 提示容器 -->
  <div id="toast-container" class="toast-container"></div>

  <script src="assets/admin.js?v=<?=filemtime(__DIR__.'/assets/admin.js')?>"></script>
</body>
</html>
