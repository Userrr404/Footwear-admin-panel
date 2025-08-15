<?php
require_once '../config.php';


if (session_status() === PHP_SESSION_NONE) { session_start(); }
$adminName = $_SESSION['admin_name'] ?? 'Admin';

$redirectTarget = 'list.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $refPath = strtolower(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?? '');
    if (preg_match('#/(product|products)/list(\.php)?$#', $refPath)) {
        $redirectTarget = BASE_URL . '/products/list.php';
    } elseif (preg_match('#/orders/list(\.php)?$#', $refPath)) {
        $redirectTarget = BASE_URL . '/orders/list.php';
    }
}

$subtitle = isset($page_subtitle) && $page_subtitle !== '' ? ' / ' . $page_subtitle : '';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />

<!-- Header / Toolbar -->
<header class="app-header">
  <div class="container py-3 d-flex align-items-center justify-content-between">
    <div class="brand-badge">
      <span class="brand-dot"></span>
      <span>Footwear Admin</span>
      <span class="text-secondary"><?= htmlspecialchars($subtitle) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2 toolbar-group">
      <span class="greeting text-secondary me-2">Hello, <strong><?= htmlspecialchars($adminName) ?></strong></span>
      <a href="<?= htmlspecialchars($redirectTarget) ?>" class="btn btn-sm btn-outline-secondary toolbar-btn">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button id="themeToggle" class="btn btn-sm btn-outline-secondary toolbar-btn" type="button" aria-pressed="false" title="Toggle theme">
        <i class="bi bi-moon-stars"></i> <span>Dark</span>
      </button>
    </div>
  </div>
</header>

<style>

  :root{
      --bg:#f5f7fb; --card:#fff; --text:#0f172a; --muted:#475569; --border:#e2e8f0;
      --input:#fff; --input-border:#cbd5e1; --primary:#4f46e5; --primary-contrast:#fff;
      --accent:#22c55e; --danger:#ef4444; --shadow:0 8px 24px rgba(2,6,23,.06);
    }
    [data-theme="dark"]{
      --bg:#0f1115; --card:#151922; --text:#e5e7eb; --muted:#9aa4b2; --border:#2a2f3a;
      --input:#1a1f2b; --input-border:#374151; --primary:#6366f1; --primary-contrast:#fff;
      --accent:#22c55e; --danger:#f87171; --shadow:0 12px 28px rgba(0,0,0,.45);
    }
        html,body{height:100%}
    body{background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;}
/* Base */
.app-header {
  position: sticky;
  top: 0;
  z-index: 1010;
  backdrop-filter: saturate(180%) blur(10px);
  background: color-mix(in oklab, var(--card) 85%, transparent);
  border-bottom: 1px solid var(--border);
}
.brand-badge {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  font-weight: 700;
  letter-spacing: .3px;
}
.brand-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: var(--primary);
}
.toolbar-btn {
  border-color: var(--input-border) !important;
  color: var(--text) !important;
}

/* 576px–767px: Compact greeting, but keep horizontal layout */
@media (max-width: 767px) and (min-width: 576px) {
  /* Container spacing */
  .app-header .container {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    gap: 0.75rem;
  }

  /* Brand section */
  .brand-badge {
    font-size: 1.05rem; /* Slightly smaller text */
    gap: 0.4rem; /* Tighter gap */
  }
  .brand-dot {
    width: 8px;
    height: 8px;
  }
  .brand-badge span.text-secondary {
    font-size: 0.85rem;
    opacity: 0.85;
  }

  /* Greeting text */
  .greeting {
    display: none;
  }

  /* Toolbar buttons: fit all text */
  .toolbar-btn {
    padding: 0.35rem 0.55rem;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
  }
  .toolbar-btn i {
    font-size: 1rem;
  }

  /* Ensure icons + text stay aligned */
  .toolbar-btn span {
    display: inline;
  }
}

/* 480px–575px: Compact single-line header */
@media (max-width: 575px) and (min-width: 480px) {
  .app-header .container {
    flex-wrap: nowrap;
    padding: 0.5rem 0.75rem;
    gap: 0.5rem;
  }

  .brand-badge {
    font-size: 1.2rem;
    flex-shrink: 0;
    gap: 0.4rem;
  }
  .brand-dot {
    width: 8px;
    height: 8px;
  }
  .brand-badge span.text-secondary {
    font-size: 0.85rem;
    opacity: 0.8;
  }

  .toolbar-group {
    flex-shrink: 0;
  }
  .toolbar-btn {
    padding: 0.3rem 0.5rem;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
  }

  .greeting {
    display: none;
  }
}


/* 425px–479px: Vertical brand section + single-line toolbar */
@media (max-width: 479px) and (min-width: 425px) {
  .app-header .container {
    flex-wrap:nowrap;
    padding: 0.5rem 0.75rem;
    gap: 0.5rem;
  }
  .brand-badge {
    font-size: 1.1rem;
    flex-shrink: 0; /* Keep brand section compact */
    gap: 0.4rem;
  }
  .brand-dot {
    width: 8px;
    height: 8px;
  }
  .toolbar-group {
    width: 100%;
    justify-content: flex-end;
  }
  .toolbar-btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.4rem;
  }
  .greeting {
    display: none;
  }
}

@media (max-width: 424px) {
  .app-header .container {
    flex-wrap:nowrap;
    padding: 0.5rem 0.75rem;
    gap: 0.5rem;
  }
  .brand-badge {
    font-size: 1.1rem;
    flex-shrink: 0; /* Keep brand section compact */
    gap: 0.4rem;
  }
  .brand-dot {
    width: 8px;
    height: 8px;
  }
  span.text-secondary{
    display:none;
  }
  .toolbar-group {
    width: 100%;
    justify-content: flex-end;
  }
  .toolbar-btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.4rem;
  }
  .greeting {
    display: none;
  }
}
</style>

<script>
(function(){
  const THEME_KEY = 'admin-theme';
  const root = document.documentElement;
  const toggleBtn = document.getElementById('themeToggle');

  const saved = localStorage.getItem(THEME_KEY) || 'light';
  root.setAttribute('data-theme', saved);
  updateThemeButton(saved);

  toggleBtn.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem(THEME_KEY, next);
    updateThemeButton(next);
  });

  function updateThemeButton(mode){
    toggleBtn.setAttribute('aria-pressed', mode === 'dark');
    toggleBtn.innerHTML = mode === 'dark'
      ? '<i class="bi bi-sun"></i> <span class="d-none d-sm-inline">Light</span>'
      : '<i class="bi bi-moon-stars"></i> <span class="d-none d-sm-inline">Dark</span>';
  }
})();
</script>
