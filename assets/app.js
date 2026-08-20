// assets/app.js - 前台与后台共用的少量 JS（更新：日期后追加星期）
(function(){
  // 保证在 DOM 就绪后执行
  document.addEventListener('DOMContentLoaded', function() {
    // --------- 公共：时钟与模式按钮（前台使用） ----------
    function updateClock(){
      const timeEl = document.getElementById('time');
      const dateEl = document.getElementById('date');
      if(!timeEl && !dateEl) return;
      const d = new Date();
      const hh = String(d.getHours()).padStart(2,'0');
      const mm = String(d.getMinutes()).padStart(2,'0');
      const ss = String(d.getSeconds()).padStart(2,'0');
      const yyyy = d.getFullYear();
      const mo = String(d.getMonth()+1).padStart(2,'0');
      const dd = String(d.getDate()).padStart(2,'0');
      // 中文星期数组（0=周日, 1=周一 ...）
      const weekdays = ['周日','周一','周二','周三','周四','周五','周六'];
      const weekday = weekdays[d.getDay()] || '';
      if (timeEl) timeEl.textContent = hh + ':' + mm + ':' + ss;
      if (dateEl) dateEl.textContent = `${yyyy}-${mo}-${dd} ${weekday}`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    document.querySelectorAll('.mode-btn').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const mode = btn.dataset.mode;
        localStorage.setItem('nav_mode', mode);
        highlightMode();
      });
    });
    function highlightMode(){
      const cur = localStorage.getItem('nav_mode') || 'v6';
      document.querySelectorAll('.mode-btn').forEach(b=>{
        if (b.dataset.mode === cur) b.style.background = 'linear-gradient(90deg,#6b5ef0,#e86aa7)';
        else b.style.background = 'rgba(255,255,255,0.08)';
      });
    }
    highlightMode();

    // --------- 滚动时固定顶部 server-name ----------
    const heroRow = document.querySelector('.hero-row');
    const stickyHeader = document.getElementById('sticky-header');
    const topNavbar = document.getElementById('top-navbar');

    if (heroRow && stickyHeader && topNavbar) {
      // 使用 IntersectionObserver 观察第一个可滚动元素 .hero-row
      // 当它不再是100%可见时，意味着页面已经开始滚动
      const observer = new IntersectionObserver((entries) => {
        const entry = entries[0];
        // intersectionRatio < 1 意味着元素不是完全可见，即页面已滚动
        if (entry.intersectionRatio < 1) {
          stickyHeader.classList.add('visible');
          topNavbar.classList.add('visible');
        } else {
          // intersectionRatio === 1 意味着元素完全可见，即页面在最顶部
          stickyHeader.classList.remove('visible');
          topNavbar.classList.remove('visible');
        }
      }, {
        root: null,
        threshold: 1.0 // 仅在“完全可见”和“不完全可见”状态切换时触发
      });

      observer.observe(heroRow);
    }

    // --------- 后台逻辑初始化（如果在后台页面） ----------
    const svcList = document.getElementById('svc-list');
    const saveOrderBtn = document.getElementById('save-order-btn');
    const btnNew = document.getElementById('btn-new');
    const adminSearch = document.getElementById('admin-search');
    const adminPageSizeSelect = document.getElementById('admin-page-size');
    const adminPrevBtn = document.getElementById('admin-prev');
    const adminNextBtn = document.getElementById('admin-next');
    const adminPageInfo = document.getElementById('admin-page-info');

    // modal 元素（可在后台存在）
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = modalOverlay ? modalOverlay.querySelector('.modal-close') || document.getElementById('modal-close') : document.getElementById('modal-close');
    const modalTitle = document.getElementById('modal-title');
    const svcForm = document.getElementById('svc-form');
    const modalReset = document.getElementById('modal-reset');

    // 如果没有后台区域就结束后台初始化（只有前台时）
    if (!svcList) {
      // 前台：绑定卡片点击（若存在）
      const cards = Array.from(document.querySelectorAll('.card'));
      cards.forEach(card=>{
        card.addEventListener('click', ()=>{
          const mode = localStorage.getItem('nav_mode') || 'v6';
          const url = card.dataset[mode];
          if (!url) {
            alert('此服务尚未填写对应链接');
            return;
          }
          window.open(url, '_blank');
        });
      });

        const searchTrigger = document.getElementById('nav-search');
        const modalOverlay = document.getElementById('search-modal-overlay');
        const modalInput = document.getElementById('search-modal-input');
        const searchResults = document.getElementById('search-modal-results');

        if (searchTrigger && modalOverlay && modalInput && searchResults) {
          const normalize = (v) => String(v || '').toLowerCase().trim();

          const allServices = Array.isArray(window.__NAV_ALL_SERVICES__) ? window.__NAV_ALL_SERVICES__ : [];
          const indexed = allServices.map(s => {
            const name = s && s.name ? String(s.name) : '';
            const description = s && s.description ? String(s.description) : '';
            const id = s && s.id != null ? String(s.id) : '';
            const linkV6 = s && s.link_v6 ? String(s.link_v6) : '';
            const linkV4 = s && s.link_v4 ? String(s.link_v4) : '';
            const linkLan = s && s.link_lan ? String(s.link_lan) : '';
            const icon = s && s.icon ? String(s.icon) : '';
            const enabled = s && s.enabled != null ? Number(s.enabled) : 0;
            const data = [id, name, description, linkV6, linkV4, linkLan].join(' ');
            return {
              id, name, description, link_v6: linkV6, link_v4: linkV4, link_lan: linkLan,
              icon, enabled, searchText: normalize(data)
            };
          });

          const getMode = () => localStorage.getItem('nav_mode') || 'v6';

          const openModal = () => {
            modalOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            setTimeout(() => modalInput.focus(), 50);
            filterAndRender(modalInput.value);
          };

          const closeModal = () => {
            modalOverlay.classList.remove('show');
            document.body.style.overflow = '';
            modalInput.value = '';
            searchResults.textContent = '';
            if (searchTrigger) searchTrigger.blur();
          };

          const resolveIcon = (icon) => {
            if (!icon) return 'favicon.ico';
            if (/^https?:\/\//i.test(icon)) return icon;
            return icon;
          };

          const renderList = (items) => {
            searchResults.textContent = '';
            if (!items.length) {
              const empty = document.createElement('div');
              empty.className = 'nav-search-empty';
              empty.textContent = '无匹配结果';
              searchResults.appendChild(empty);
              return;
            }
            const frag = document.createDocumentFragment();
            items.forEach(s => {
              const row = document.createElement('div');
              row.className = 'nav-search-item' + (s.enabled === 0 ? ' is-disabled' : '');
              row.dataset.id = s.id;
              row.dataset.v6 = s.link_v6 || '';
              row.dataset.v4 = s.link_v4 || '';
              row.dataset.lan = s.link_lan || '';

              const img = document.createElement('img');
              img.className = 'si-icon';
              img.alt = '';
              img.src = resolveIcon(s.icon);

              const main = document.createElement('div');
              main.className = 'si-main';

              const title = document.createElement('div');
              title.className = 'si-title';
              title.textContent = s.name || '';

              const desc = document.createElement('div');
              desc.className = 'si-desc';
              desc.textContent = s.description || '';

              main.appendChild(title);
              main.appendChild(desc);
              row.appendChild(img);
              row.appendChild(main);
              frag.appendChild(row);
            });
            searchResults.appendChild(frag);
          };

          const filterAndRender = (raw) => {
            const q = normalize(raw);
            const parts = q ? q.split(/\s+/).filter(Boolean) : [];
            const out = parts.length
              ? indexed.filter(s => parts.every(p => s.searchText.includes(p)))
              : indexed.slice();
            renderList(out);
          };

          searchTrigger.addEventListener('click', openModal);

          modalInput.addEventListener('input', () => {
            filterAndRender(modalInput.value);
          });

          searchResults.addEventListener('click', (e) => {
            const item = e.target.closest('.nav-search-item');
            if (!item) return;
            const mode = getMode();
            const url = item.dataset[mode];
            if (!url) {
              alert('此服务尚未填写对应链接');
              return;
            }
            window.open(url, '_blank');
            closeModal();
          });

          modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal();
          });

          const escTip = modalOverlay.querySelector('.search-modal-esc');
          if (escTip) escTip.addEventListener('click', closeModal);

          document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalOverlay.classList.contains('show')) {
              closeModal();
            }
          });
        }

        return;
      }

    // ---------- modal 显示/隐藏 ----------
    function showModal() {
      if (!modalOverlay) return;
      modalOverlay.classList.add('show');
      modalOverlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(()=> {
        const f = svcForm ? svcForm.querySelector('input[name="name"]') : null;
        if (f) f.focus();
      }, 140);
    }
    function hideModal() {
      if (!modalOverlay) return;
      modalOverlay.classList.remove('show');
      modalOverlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    if (modalClose) modalClose.addEventListener('click', hideModal);

    // ---------- 其余后台逻辑（拖拽 / 编辑 / 删除 / 状态切换 / 保存排序 / 表单提交 / 获取图标 / 上传图标） ----------
    const normalizeAdmin = (v) => String(v || '').toLowerCase().trim();
    let adminPage = 1;
    let adminPageSize = 20;
    try {
      const saved = Number(localStorage.getItem('admin_page_size') || '');
      if ([10, 20, 50, 100].includes(saved)) adminPageSize = saved;
    } catch (e) {}

    function buildRowSearchText(tr) {
      const tds = tr.querySelectorAll('td');
      const parts = [];
      if (tds[0]) parts.push(tds[0].textContent);
      if (tds[2]) parts.push(tds[2].textContent);
      if (tds[3]) parts.push(tds[3].textContent);
      if (tds[4]) parts.push(tds[4].textContent);
      if (tds[5]) parts.push(tds[5].textContent);
      if (tds[6]) parts.push(tds[6].textContent);
      tr.dataset.searchText = normalizeAdmin(parts.join(' '));
    }

    function applyAdminSearchAndPagination() {
      if (!svcList) return;
      const rows = Array.from(svcList.querySelectorAll('tr'));
      const rowOrder = new Map();
      rows.forEach((tr, i) => rowOrder.set(tr, i));
      rows.forEach(tr => {
        if (!tr.dataset.searchText) buildRowSearchText(tr);
      });

      const q = normalizeAdmin(adminSearch ? adminSearch.value : '');
      const parts = q ? q.split(/\s+/).filter(Boolean) : [];

      const matched = [];
      rows.forEach(tr => {
        const hay = tr.dataset.searchText || '';
        const ok = parts.length ? parts.every(p => hay.includes(p)) : true;
        tr.dataset.match = ok ? '1' : '0';
        if (ok) matched.push(tr);
      });

      matched.sort((a, b) => {
        const ca = a.querySelector('.home-checkbox');
        const cb = b.querySelector('.home-checkbox');
        const ha = ca && ca.checked ? 1 : 0;
        const hb = cb && cb.checked ? 1 : 0;
        if (ha !== hb) return hb - ha;
        return (rowOrder.get(a) ?? 0) - (rowOrder.get(b) ?? 0);
      });

      const total = matched.length;
      const totalPages = Math.max(1, Math.ceil(total / adminPageSize));
      if (adminPage > totalPages) adminPage = totalPages;
      if (adminPage < 1) adminPage = 1;

      const start = (adminPage - 1) * adminPageSize;
      const end = start + adminPageSize;

      rows.forEach(tr => { tr.style.display = 'none'; });
      matched.slice(start, end).forEach(tr => { tr.style.display = ''; });

      if (adminPageInfo) adminPageInfo.textContent = `第 ${adminPage} / ${totalPages} 页，共 ${total} 条`;
      if (adminPrevBtn) adminPrevBtn.disabled = adminPage <= 1;
      if (adminNextBtn) adminNextBtn.disabled = adminPage >= totalPages;
    }

    let dragEl = null;
    svcList.addEventListener('dragstart', (e)=>{
      let tr = e.target.closest('tr[draggable="true"]');
      if (!tr) return;
      dragEl = tr;
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', tr.dataset.id || ''); } catch (err) {}
      tr.style.opacity = '0.4';
    });
    svcList.addEventListener('dragend', (e)=>{
      if (dragEl) dragEl.style.opacity = '';
      dragEl = null;
      svcList.querySelectorAll('tr').forEach(r=>r.classList.remove('drag-over'));
    });
    svcList.addEventListener('dragover', (e)=>{
      e.preventDefault();
      const tr = e.target.closest('tr[draggable="true"]');
      if (!tr || tr === dragEl) return;
      tr.classList.add('drag-over');
    });
    svcList.addEventListener('dragleave', (e)=>{
      const tr = e.target.closest('tr[draggable="true"]');
      if (tr) tr.classList.remove('drag-over');
    });
    svcList.addEventListener('drop', (e)=>{
      e.preventDefault();
      const target = e.target.closest('tr[draggable="true"]');
      if (!target || !dragEl || target === dragEl) return;
      const rect = target.getBoundingClientRect();
      const offset = e.clientY - rect.top;
      if (offset > rect.height / 2) {
        target.parentNode.insertBefore(dragEl, target.nextSibling);
      } else {
        target.parentNode.insertBefore(dragEl, target);
      }
      svcList.querySelectorAll('tr').forEach(r=>r.classList.remove('drag-over'));
      applyAdminSearchAndPagination();
    });

    if (adminPageSizeSelect) {
      adminPageSizeSelect.value = String(adminPageSize);
      adminPageSizeSelect.addEventListener('change', () => {
        const v = Number(adminPageSizeSelect.value);
        if ([10, 20, 50, 100].includes(v)) {
          adminPageSize = v;
          try { localStorage.setItem('admin_page_size', String(v)); } catch (e) {}
          adminPage = 1;
          applyAdminSearchAndPagination();
        }
      });
    }
    if (adminSearch) {
      adminSearch.addEventListener('input', () => {
        adminPage = 1;
        applyAdminSearchAndPagination();
      });
      document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (document.activeElement !== adminSearch) return;
        if (!adminSearch.value) return;
        adminSearch.value = '';
        adminPage = 1;
        applyAdminSearchAndPagination();
        adminSearch.blur();
      });
    }
    if (adminPrevBtn) {
      adminPrevBtn.addEventListener('click', () => {
        adminPage = Math.max(1, adminPage - 1);
        applyAdminSearchAndPagination();
      });
    }
    if (adminNextBtn) {
      adminNextBtn.addEventListener('click', () => {
        adminPage = adminPage + 1;
        applyAdminSearchAndPagination();
      });
    }

    applyAdminSearchAndPagination();

    if (saveOrderBtn) {
      saveOrderBtn.addEventListener('click', async ()=>{
        const rows = Array.from(svcList.querySelectorAll('tr'));
        const layout = rows
          .map(tr => {
            const id = tr.dataset.id;
            const homeCheckbox = tr.querySelector('.home-checkbox');
            return {
              id,
              show_home: homeCheckbox ? (homeCheckbox.checked ? 1 : 0) : 1
            };
          })
          .filter(x => !!x.id);

        if (!layout.length) return alert('没有可保存的项');
        if (!confirm('确认保存当前布局（排序 + 首页显示）吗？')) return;

        try {
          const res = await fetch('api.php?action=layout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ layout })
          });
          const data = await res.json();
          if (data.ok) {
            alert('布局保存成功，页面将刷新');
            location.reload();
          } else {
            alert('保存失败: ' + (data.err || '未知错误'));
          }
        } catch (err) {
          alert('请求失败: ' + err);
        }
      });
    }

    svcList.addEventListener('click', async (e)=>{
      if (e.target.matches('.edit-btn')) {
        const id = e.target.dataset.id;
        if (!id) return;
        try {
          const res = await fetch('api.php?action=get&id=' + encodeURIComponent(id));
          const data = await res.json();
          if (!data.ok) { alert('获取失败'); return; }
          fillForm(data.row);
          modalTitle && (modalTitle.textContent = '编辑服务 #' + id);
          showModal();
        } catch (err) {
          alert('请求失败: ' + err);
        }
        return;
      }
      if (e.target.matches('.del-btn')) {
        const id = e.target.dataset.id;
        if (!id) return;
        if (!confirm('确认删除？')) return;
        try {
          const res = await fetch('api.php?action=delete&id=' + encodeURIComponent(id), { method: 'POST' });
          const data = await res.json();
          if (data.ok) {
            location.reload();
          } else {
            alert('删除失败: ' + (data.err || '未知错误'));
          }
        } catch (err) {
          alert('请求失败: ' + err);
        }
        return;
      }
      if (e.target.matches('.status-badge')) {
        const badge = e.target;
        const id = badge.dataset.id;
        if (!id) return;
        const currentlyEnabled = badge.classList.contains('enabled');
        if (currentlyEnabled) {
          badge.classList.remove('enabled'); badge.classList.add('disabled'); badge.textContent = '禁用';
        } else {
          badge.classList.remove('disabled'); badge.classList.add('enabled'); badge.textContent = '启用';
        }
        try {
          const res = await fetch('api.php?action=toggle&id=' + encodeURIComponent(id), { method: 'POST' });
          const data = await res.json();
          if (!data.ok) {
            if (currentlyEnabled) {
              badge.classList.remove('disabled'); badge.classList.add('enabled'); badge.textContent = '启用';
            } else {
              badge.classList.remove('enabled'); badge.classList.add('disabled'); badge.textContent = '禁用';
            }
            alert('切换失败: ' + (data.err || '未知错误'));
          }
        } catch (err) {
          if (currentlyEnabled) {
            badge.classList.remove('disabled'); badge.classList.add('enabled'); badge.textContent = '启用';
          } else {
            badge.classList.remove('enabled'); badge.classList.add('disabled'); badge.textContent = '禁用';
          }
          alert('请求失败: ' + err);
        }
        return;
      }

      // 获取图标
      if (e.target.matches('.fetch-icon-btn')) {
        const id = e.target.dataset.id;
        if (!id) return;
        try {
          const res = await fetch('api.php?action=fetch_icon&id=' + encodeURIComponent(id), { method: 'POST' });
          const data = await res.json();
          if (data.ok) {
            // 刷新页面以显示新图标
            alert('获取并保存图标成功');
            location.reload();
          } else {
            alert('获取失败: ' + (data.err || '未知错误'));
          }
        } catch (err) {
          alert('请求失败: ' + err);
        }
        return;
      }

      // 上传图标（行内按钮，动态文件输入）
      if (e.target.matches('.upload-icon-btn')) {
        const id = e.target.dataset.id;
        if (!id) return;
        // 创建临时 file input
        const inp = document.createElement('input');
        inp.type = 'file';
        inp.accept = 'image/*,image/x-icon';
        inp.style.display = 'none';
        document.body.appendChild(inp);
        inp.addEventListener('change', async () => {
          if (!inp.files || !inp.files[0]) {
            document.body.removeChild(inp);
            return;
          }
          const fd = new FormData();
          fd.append('id', id);
          fd.append('icon', inp.files[0]);
          try {
            const res = await fetch('api.php?action=upload_icon', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
              alert('上传成功');
              location.reload();
            } else {
              alert('上传失败: ' + (data.err || '未知错误'));
            }
          } catch (err) {
            alert('请求失败: ' + err);
          } finally {
            document.body.removeChild(inp);
          }
        });
        // 触发选择
        inp.click();
        return;
      }
    });

    if (btnNew) {
      btnNew.addEventListener('click', ()=>{
        modalTitle && (modalTitle.textContent = '新增服务');
        resetFormFields();
        showModal();
      });
    }

    function fillForm(row) {
      if (!svcForm) return;
      svcForm.id.value = row.id || '';
      svcForm.name.value = row.name || '';
      svcForm.description.value = row.description || '';
      svcForm.link_v6.value = row.link_v6 || '';
      svcForm.link_v4.value = row.link_v4 || '';
      svcForm.link_lan.value = row.link_lan || '';
      svcForm.enabled.checked = row.enabled == 1;
      if (svcForm.show_home) svcForm.show_home.checked = row.show_home == 1;
      // 图标字段已移出弹窗
    }
    function resetFormFields() {
      if (!svcForm) return;
      svcForm.reset();
      svcForm.id.value = '';
      if (svcForm.show_home) svcForm.show_home.checked = true;
    }

    if (modalReset) {
      modalReset.addEventListener('click', (e)=>{
        e.preventDefault();
        resetFormFields();
      });
    }

    if (svcForm) {
      svcForm.addEventListener('submit', async (ev)=>{
        ev.preventDefault();
        const fd = new FormData(svcForm);
        try {
          const res = await fetch('api.php?action=save', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.ok) {
            alert('保存成功');
            hideModal();
            location.reload();
          } else {
            alert('保存失败: ' + (data.err || '未知错误'));
          }
        } catch (err) {
          alert('请求失败: ' + err);
        }
      });
    }

    if (!svcForm && modalClose) {
      modalClose.addEventListener('click', hideModal);
    }

  }); // DOMContentLoaded end
})();
