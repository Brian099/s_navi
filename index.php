<?php
// index.php - 前台导航页面（JSON 存储）
// 优先读取 .env 中的 NAV_SERVER_NAME 或 SERVER_NAME（如之前实现）

function parse_dotenv($path) {
    $out = [];
    if (!file_exists($path)) return $out;
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return $out;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $val) = array_map('trim', explode('=', $line, 2));
        $val = preg_replace('/^([\'"])(.*)\\1$/', '$2', $val);
        $out[$key] = $val;
    }
    return $out;
}

function is_valid_name($v) {
    return isset($v) && $v !== '' && $v !== '_' && $v !== null;
}

$envPath = __DIR__ . '/.env';
$dot = parse_dotenv($envPath);

$serverName = null;
if (isset($dot['NAV_SERVER_NAME']) && is_valid_name($dot['NAV_SERVER_NAME'])) {
    $serverName = $dot['NAV_SERVER_NAME'];
} elseif (isset($dot['SERVER_NAME']) && is_valid_name($dot['SERVER_NAME'])) {
    $serverName = $dot['SERVER_NAME'];
} else {
    $envNav = getenv('NAV_SERVER_NAME');
    $envSrv = getenv('SERVER_NAME');
    if (is_valid_name($envNav)) $serverName = $envNav;
    elseif (is_valid_name($envSrv)) $serverName = $envSrv;
}

if (!is_valid_name($serverName)) $serverName = 'BrianServer';

// 读取服务数据
$dataFile = __DIR__ . '/data/services.json';
if (!file_exists($dataFile)) {
    if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0755, true);
    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
$raw = file_get_contents($dataFile);
$services = json_decode($raw, true);
if (!is_array($services)) $services = [];

usort($services, function($a,$b){
    $sa = $a['sort_order'] ?? 0; $sb = $b['sort_order'] ?? 0;
    if ($sa === $sb) return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
    return $sa <=> $sb;
});
$allServices = $services;
$services = array_filter($services, function($s){
    $enabled = ($s['enabled'] ?? 0) == 1;
    $showHome = ($s['show_home'] ?? 0) == 1;
    return $enabled && $showHome;
});

function getBodyCSS() {
    $uploadsDir = 'uploads/';
    
    if (is_dir($uploadsDir)) {
        $files = scandir($uploadsDir);
        $imageExtensions = ['.jpg', '.jpeg', '.png', '.webp', '.gif'];
        
        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            $ext = '.' . strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            // 检查文件名是否为background且是图片格式
            if (strtolower($fileName) === 'background' && in_array($ext, $imageExtensions)) {
                $imagePath = $uploadsDir . $file;
                return "background: url('$imagePath') no-repeat center center fixed; 
                        background-size: cover;";
            }
        }
    }
}

// 使用示例
$bodyCSS = getBodyCSS();

?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($serverName)?> - 导航</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/style.css?v=<?=filemtime(__DIR__.'/assets/style.css')?>">
</head>
<body style="<?php echo $bodyCSS; ?>">
  <div class="bg"></div>

  <!-- 顶部悬浮导航层（滚动时带背景遮罩） -->
  <div id="top-navbar" class="top-navbar">
    <!-- 固定在左上角的服务器名称（页面向下滚动时显示） -->
    <div id="sticky-header" class="mode-top-left">
      <h2 class="sticky-server-name"><?=htmlspecialchars($serverName)?></h2>
    </div>

    <!-- 右上角组（搜索及模式切换） -->
    <div class="mode-top-right">
      <input id="nav-search" class="nav-search" type="search" placeholder="搜索服务…" autocomplete="off">
      <button data-mode="v6" class="mode-btn">V6</button>
      <button data-mode="v4" class="mode-btn">V4</button>
      <button data-mode="lan" class="mode-btn">局域网</button>
    </div>
  </div>
  <!-- 全局搜索弹窗 -->
  <div id="search-modal-overlay" class="search-modal-overlay">
    <div class="search-modal">
      <div class="search-modal-top">
        <input id="search-modal-input" class="search-modal-input" type="search" placeholder="输入关键词搜索服务..." autocomplete="off">
        <div class="search-modal-tips">
          <span class="search-modal-esc">ESC</span>
        </div>
      </div>
      <div id="search-modal-results" class="nav-search-panel"></div>
    </div>
  </div>

  <!-- 中间独立一行：服务器名称与时间（名称左，时间右两行显示） -->
  <header class="hero-row">
    <div class="hero">
      <h1 id="server-name"><?=htmlspecialchars($serverName)?></h1><p class="dot">|</p>
      <div class="clock-box" aria-hidden="false">
        <div id="time" class="time">--:--:--</div>
        <div id="date" class="date">----/--/--</div>
      </div>
    </div>
  </header>

  <main class="container">
    <section class="apps">
      <div class="grid">
        <?php foreach ($services as $s): ?>
          <?php
            $icon = 'favicon.ico';
            if (!empty($s['icon']) && (strpos($s['icon'],'http://') === 0 || strpos($s['icon'],'https://') === 0)) {
                $icon = $s['icon'];
            } elseif (!empty($s['icon']) && file_exists(__DIR__ . '/' . $s['icon'])) {
                $icon = $s['icon'];
            }
          ?>
          <div class="card" data-id="<?=htmlspecialchars($s['id'])?>"
               data-v6="<?=htmlspecialchars($s['link_v6'] ?? '')?>"
               data-v4="<?=htmlspecialchars($s['link_v4'] ?? '')?>"
               data-lan="<?=htmlspecialchars($s['link_lan'] ?? '')?>">
            <img class="icon" src="<?=htmlspecialchars($icon)?>" alt="">
            <div class="meta">
              <div class="title"><?=htmlspecialchars($s['name'] ?? '')?></div>
              <div class="desc"><?=htmlspecialchars($s['description'] ?? '')?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer class="foot">
    <small>Powered by Simple-Nav • <a href="admin.php">管理</a></small>
  </footer>

  <script>
    window.__NAV_ALL_SERVICES__ = <?=json_encode($allServices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
  </script>
  <script src="assets/app.js?v=<?=filemtime(__DIR__.'/assets/app.js')?>"></script>
</body>
</html>
