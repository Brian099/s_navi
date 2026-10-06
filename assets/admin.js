// assets/admin.js - 后台管理专用脚本
(function(){
  document.addEventListener('DOMContentLoaded', function() {
    const svcList = document.getElementById('svc-list');
    const saveOrderBtn = document.getElementById('save-order-btn');
    const btnNew = document.getElementById('btn-new');
    const adminSearch = document.getElementById('admin-search');
    const searchClearBtn = document.getElementById('search-clear-btn');
    const adminPageSizeSelect = document.getElementById('admin-page-size');
    const adminPrevBtn = document.getElementById('admin-prev');
    const adminNextBtn = document.getElementById('admin-next');
    const adminPageInfo = document.getElementById('admin-page-info');
    const toastContainer = document.getElementById('toast-container');
    const layoutDirtyBadge = document.getElementById('layout-change-badge');

    // modal 元素
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = modalOverlay ? (modalOverlay.querySelector('.modal-close-btn') || modalOverlay.querySelector('.modal-close') || document.getElementById('modal-close')) : document.getElementById('modal-close');
    const modalTitle = document.getElementById('modal-title');
    const svcForm = document.getElementById('svc-form');
    const modalReset = document.getElementById('modal-reset');

    if (!svcList) return;

    // Toast 提示助手函数
    function showToast(msg, type = 'info', duration = 3000) {
      if (!toastContainer) {
        alert(msg);
        return;
      }
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;

      let iconHtml = '';
      if (type === 'success') {
        iconHtml = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#10b981" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
      } else if (type === 'error') {
        iconHtml = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
      } else {
        iconHtml = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#818cf8" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
      }

      toast.innerHTML = `${iconHtml}<span>${msg}</span>`;
      toastContainer.appendChild(toast);

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 250);
      }, duration);
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

    if (modalOverlay) {
      modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) hideModal();
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalOverlay.classList.contains('show')) {
          hideModal();
        }
      });
    }

    // ---------- 动态数据统计刷新 ----------
    function refreshStats() {
      const rows = Array.from(svcList.querySelectorAll('tr.svc-row'));
      const total = rows.length;
      let enabled = 0;
      let home = 0;
      let disabled = 0;

      rows.forEach(tr => {
        const badge = tr.querySelector('.status-badge');
        const homeCb = tr.querySelector('.home-checkbox');
        if (badge && badge.classList.contains('enabled')) {
          enabled++;
        } else {
          disabled++;
        }
        if (homeCb && homeCb.checked) {
          home++;
        }
      });

      const statTotal = document.getElementById('stat-total');
      const statEnabled = document.getElementById('stat-enabled');
      const statHome = document.getElementById('stat-home');
      const statDisabled = document.getElementById('stat-disabled');

      if (statTotal) statTotal.textContent = total;
      if (statEnabled) statEnabled.textContent = enabled;
      if (statHome) statHome.textContent = home;
      if (statDisabled) statDisabled.textContent = disabled;
    }

    function markLayoutDirty() {
      if (layoutDirtyBadge) {
        layoutDirtyBadge.style.display = 'inline-block';
      }
    }

    // ---------- 后台搜索与分页 ----------
    const normalizeAdmin = (v) => String(v || '').toLowerCase().trim();
    let adminPage = 1;
    let adminPageSize = 20;
    try {
      const saved = Number(localStorage.getItem('admin_page_size') || '');
      if ([10, 20, 50, 100].includes(saved)) adminPageSize = saved;
    } catch (e) {}

    function buildRowSearchText(tr) {
      const title = tr.querySelector('.service-title') ? tr.querySelector('.service-title').textContent : '';
      const desc = tr.querySelector('.service-desc') ? tr.querySelector('.service-desc').textContent : '';
      const chips = Array.from(tr.querySelectorAll('.net-chip')).map(c => c.textContent).join(' ');
      const id = tr.dataset.id || '';
      tr.dataset.searchText = normalizeAdmin([id, title, desc, chips].join(' '));
    }

    function applyAdminSearchAndPagination() {
      if (!svcList) return;
      const rows = Array.from(svcList.querySelectorAll('tr.svc-row'));
      const rowOrder = new Map();
      rows.forEach((tr, i) => rowOrder.set(tr, i));
      rows.forEach(tr => {
        if (!tr.dataset.searchText) buildRowSearchText(tr);
      });

      const q = normalizeAdmin(adminSearch ? adminSearch.value : '');
      const parts = q ? q.split(/\s+/).filter(Boolean) : [];

      if (searchClearBtn) {
        searchClearBtn.style.display = q ? 'block' : 'none';
      }

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

      if (adminPageInfo) adminPageInfo.textContent = `第 ${adminPage} / ${totalPages} 页（共 ${total} 项）`;
      if (adminPrevBtn) adminPrevBtn.disabled = adminPage <= 1;
      if (adminNextBtn) adminNextBtn.disabled = adminPage >= totalPages;
    }

    // 拖拽排序
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
      markLayoutDirty();
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

    if (searchClearBtn) {
      searchClearBtn.addEventListener('click', () => {
        if (adminSearch) {
          adminSearch.value = '';
          adminPage = 1;
          applyAdminSearchAndPagination();
          adminSearch.focus();
        }
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

    // 监听首页显示勾选变化
    svcList.addEventListener('change', (e) => {
      if (e.target.matches('.home-checkbox')) {
        markLayoutDirty();
        refreshStats();
      }
    });

    // 复制网络链接快捷操作
    svcList.addEventListener('click', (e) => {
      const chip = e.target.closest('.net-chip.has-link');
      if (chip) {
        const val = chip.querySelector('.net-val');
        if (val && val.textContent && val.textContent !== '—') {
          navigator.clipboard.writeText(val.textContent).then(() => {
            showToast(`已复制链接: ${val.textContent}`, 'success');
          }).catch(() => {});
        }
      }
    });

    // 保存排序与布局
    if (saveOrderBtn) {
      saveOrderBtn.addEventListener('click', async ()=>{
        const rows = Array.from(svcList.querySelectorAll('tr.svc-row'));
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

        if (!layout.length) return showToast('没有可保存的项', 'error');

        try {
          saveOrderBtn.disabled = true;
          saveOrderBtn.style.opacity = '0.7';
          const res = await fetch('api.php?action=layout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ layout })
          });
          const data = await res.json();
          if (data.ok) {
            showToast('布局与排序已成功保存！', 'success');
            if (layoutDirtyBadge) layoutDirtyBadge.style.display = 'none';
            setTimeout(() => location.reload(), 600);
          } else {
            showToast('保存失败: ' + (data.err || '未知错误'), 'error');
          }
        } catch (err) {
          showToast('请求失败: ' + err, 'error');
        } finally {
          saveOrderBtn.disabled = false;
          saveOrderBtn.style.opacity = '1';
        }
      });
    }

    // 行操作事件监听
    svcList.addEventListener('click', async (e)=>{
      // 编辑按钮
      const editBtn = e.target.closest('.edit-btn');
      if (editBtn) {
        const id = editBtn.dataset.id;
        if (!id) return;
        try {
          const res = await fetch('api.php?action=get&id=' + encodeURIComponent(id));
          const data = await res.json();
          if (!data.ok) { showToast('获取服务详情失败', 'error'); return; }
          fillForm(data.row);
          if (modalTitle) modalTitle.textContent = `编辑服务 #${id}`;
          showModal();
        } catch (err) {
          showToast('请求失败: ' + err, 'error');
        }
        return;
      }

      // 删除按钮
      const delBtn = e.target.closest('.del-btn');
      if (delBtn) {
        const id = delBtn.dataset.id;
        if (!id) return;
        if (!confirm(`确定要永久删除服务 #${id} 吗？`)) return;
        try {
          const res = await fetch('api.php?action=delete&id=' + encodeURIComponent(id), { method: 'POST' });
          const data = await res.json();
          if (data.ok) {
            showToast('删除成功', 'success');
            const row = svcList.querySelector(`tr[data-id="${id}"]`);
            if (row) {
              row.style.opacity = '0';
              setTimeout(() => {
                row.remove();
                refreshStats();
                applyAdminSearchAndPagination();
              }, 200);
            }
          } else {
            showToast('删除失败: ' + (data.err || '未知错误'), 'error');
          }
        } catch (err) {
          showToast('请求失败: ' + err, 'error');
        }
        return;
      }

      // 状态切换按钮
      const badge = e.target.closest('.status-badge');
      if (badge) {
        const id = badge.dataset.id;
        if (!id) return;
        const currentlyEnabled = badge.classList.contains('enabled');
        const spanText = badge.querySelector('span:last-child') || badge;

        if (currentlyEnabled) {
          badge.classList.remove('enabled'); badge.classList.add('disabled'); spanText.textContent = '禁用'; badge.title = '点击启用';
        } else {
          badge.classList.remove('disabled'); badge.classList.add('enabled'); spanText.textContent = '启用'; badge.title = '点击停用';
        }
        refreshStats();

        try {
          const res = await fetch('api.php?action=toggle&id=' + encodeURIComponent(id), { method: 'POST' });
          const data = await res.json();
          if (!data.ok) {
            if (currentlyEnabled) {
              badge.classList.remove('disabled'); badge.classList.add('enabled'); spanText.textContent = '启用'; badge.title = '点击停用';
            } else {
              badge.classList.remove('enabled'); badge.classList.add('disabled'); spanText.textContent = '禁用'; badge.title = '点击启用';
            }
            refreshStats();
            showToast('切换状态失败: ' + (data.err || '未知错误'), 'error');
          } else {
            showToast(`服务 #${id} 状态已切换为 ${data.enabled ? '启用' : '禁用'}`, 'success');
          }
        } catch (err) {
          if (currentlyEnabled) {
            badge.classList.remove('disabled'); badge.classList.add('enabled'); spanText.textContent = '启用';
          } else {
            badge.classList.remove('enabled'); badge.classList.add('disabled'); spanText.textContent = '禁用';
          }
          refreshStats();
          showToast('请求失败: ' + err, 'error');
        }
        return;
      }

      // 抓取图标
      const fetchBtn = e.target.closest('.fetch-icon-btn');
      if (fetchBtn) {
        const id = fetchBtn.dataset.id;
        if (!id) return;
        try {
          fetchBtn.disabled = true;
          showToast('正在自动尝试获取 Favicon 图标...', 'info');
          const res = await fetch('api.php?action=fetch_icon&id=' + encodeURIComponent(id), { method: 'POST' });
          const data = await res.json();
          if (data.ok) {
            showToast('获取并保存图标成功！', 'success');
            const iconImg = document.getElementById('icon-' + id);
            if (iconImg && data.icon) {
              iconImg.src = data.icon + '?t=' + Date.now();
            }
          } else {
            showToast('获取失败: ' + (data.err || '未知错误'), 'error');
          }
        } catch (err) {
          showToast('请求失败: ' + err, 'error');
        } finally {
          fetchBtn.disabled = false;
        }
        return;
      }

      // 上传图标
      const uploadBtn = e.target.closest('.upload-icon-btn');
      if (uploadBtn) {
        const id = uploadBtn.dataset.id;
        if (!id) return;
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
            showToast('正在上传图标...', 'info');
            const res = await fetch('api.php?action=upload_icon', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
              showToast('图标上传成功！', 'success');
              const iconImg = document.getElementById('icon-' + id);
              if (iconImg && data.icon) {
                iconImg.src = data.icon + '?t=' + Date.now();
              }
            } else {
              showToast('上传失败: ' + (data.err || '未知错误'), 'error');
            }
          } catch (err) {
            showToast('请求失败: ' + err, 'error');
          } finally {
            document.body.removeChild(inp);
          }
        });
        inp.click();
        return;
      }
    });

    if (btnNew) {
      btnNew.addEventListener('click', ()=>{
        if (modalTitle) modalTitle.textContent = '新增服务';
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
    }

    function resetFormFields() {
      if (!svcForm) return;
      svcForm.reset();
      svcForm.id.value = '';
      if (svcForm.show_home) svcForm.show_home.checked = true;
      if (svcForm.enabled) svcForm.enabled.checked = true;
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
          const submitBtn = svcForm.querySelector('button[type="submit"]');
          if (submitBtn) submitBtn.disabled = true;
          const res = await fetch('api.php?action=save', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.ok) {
            showToast('服务保存成功！', 'success');
            hideModal();
            setTimeout(() => location.reload(), 400);
          } else {
            showToast('保存失败: ' + (data.err || '未知错误'), 'error');
          }
        } catch (err) {
          showToast('请求失败: ' + err, 'error');
        } finally {
          const submitBtn = svcForm.querySelector('button[type="submit"]');
          if (submitBtn) submitBtn.disabled = false;
        }
      });
    }

  });
})();
