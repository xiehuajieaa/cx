let products = [];
let admins = [];
let productTypes = [];
let productTemplates = [];
let galleryImages = [];
let systemLogs = [];
let currentAdminUsername = '';

// 分页状态
let currentPage = 1;
let totalProducts = 0;
let pageSize = 10;

// 日志分页状态
let currentLogPage = 1;
let totalLogs = 0;
let logPageSize = 20;

// 安全转义 HTML 字符
function escapeHTML(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// 页面初始化：检查登录状态
async function checkAuth() {
    try {
        const res = await fetch('./api/admin/check_auth.php');
        const data = await res.json();
        
        if (!res.ok) {
            window.location.href = 'login.html';
            return;
        }
        
        // 确保获取到了用户名
        if (data && data.username) {
            currentAdminUsername = data.username;
            document.getElementById('currentAdminName').innerText = data.username;
            
            // 只有 admin 用户可以看到日志控制按钮和管理员操作按钮
            if (currentAdminUsername === 'admin') {
                const logControls = document.getElementById('adminLogControls');
                if (logControls) {
                    logControls.style.setProperty('display', 'flex', 'important');
                }
                
                const addAdminBtn = document.getElementById('addAdminBtn');
                if (addAdminBtn) {
                    addAdminBtn.style.display = 'block';
                }
                
                document.querySelectorAll('.admin-only-cell').forEach(el => {
                    el.style.display = 'table-cell';
                });
            }
        } else {
            document.getElementById('currentAdminName').innerText = '未知管理员';
        }
        
        loadProducts();
        loadAdmins();
        loadTypes();
        loadTemplates();
        loadLogs();
        loadStats();
    } catch (e) {
        console.error('Auth check error:', e);
        window.location.href = 'login.html';
    }
}

// 加载站点配置（ICP/公安备案/版权信息）到页脚
(async function loadSiteConfig() {
    try {
        const res = await fetch('./api/site_config.php');
        const data = await res.json();
        const copyrightEl = document.getElementById('adminFooterCopyright');
        const beianEl = document.getElementById('adminFooterBeian');
        if (copyrightEl && data.copyright_text) {
            copyrightEl.textContent = data.copyright_text;
        }
        if (!beianEl) return;
        let html = '';
        if (data.icp_no) html += `<a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener">${data.icp_no}</a>`;
        if (data.gongan_no) html += (html ? ' &nbsp; ' : '') + `<a href="https://beian.mps.gov.cn/#/query/webSearch" target="_blank" rel="noopener">${data.gongan_no}</a>`;
        if (html) beianEl.innerHTML = html;
    } catch(e) {}
})();

document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    
    // 监听图库弹窗显示，实时加载图片
    const galleryModalEl = document.getElementById('galleryModal');
    galleryModalEl.addEventListener('show.bs.modal', loadGallery);
    
    // 关键修复：关闭图库后恢复主窗口滚动并确保主窗口依然显示
    galleryModalEl.addEventListener('hidden.bs.modal', () => {
        if (document.getElementById('productModal').classList.contains('show')) {
            document.body.classList.add('modal-open');
        }
    });
});

async function logout() {
    if (!confirm('确定退出登录吗？')) return;
    try {
        const res = await fetch('./api/admin/logout.php');
        if (res.ok) window.location.href = 'login.html';
    } catch (e) { alert('登出失败'); }
}

function switchView(view, el) {
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    el.classList.add('active');

    document.querySelectorAll('.section-view').forEach(v => v.classList.remove('active'));
    document.getElementById(view + 'View').classList.add('active');
    
    // 切换到系统设置时自动加载设置
    if (view === 'settings') {
        loadSettings();
    }
}

async function loadStats() {
    try {
        const res = await fetch('./api/admin/stats.php');
        if (res.ok) {
            const data = await res.json();
            document.getElementById('statTotal').innerText = data.total_products ?? '-';
            document.getElementById('statTypes').innerText = data.total_types ?? '-';
            document.getElementById('statTemplates').innerText = data.total_templates ?? '-';
            document.getElementById('statImages').innerText = data.total_images ?? '-';
        }
    } catch (e) { /* 统计卡片暂时不可用，保持默认 */ }
}

// --- Products Logic ---
async function loadProducts(page = 1) {
    currentPage = page;
    pageSize = document.getElementById('pageSize').value;
    const search = document.getElementById('searchKeyword').value;
    const type = document.getElementById('filterType').value;
    
    try {
        const res = await fetch(`./api/admin/list.php?page=${currentPage}&limit=${pageSize}&search=${encodeURIComponent(search)}&type=${encodeURIComponent(type)}`);
        const data = await res.json();
        products = data.products;
        totalProducts = data.total;
        renderProducts();
        renderPagination();
    } catch (e) { console.error(e); }
}

function resetFilters() {
    document.getElementById('searchKeyword').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('pageSize').value = '10';
    loadProducts(1);
}

function renderProducts() {
    const list = document.getElementById('productList');
    if (products.length === 0) {
        list.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted">暂无符合条件的产品</td></tr>';
        return;
    }
    list.innerHTML = products.map(p => `
        <tr>
            <td><input type="checkbox" class="form-check-input product-check" value="${p.id}"></td>
            <td><img src="${p.image_url || 'https://via.placeholder.com/50'}" class="product-thumb"></td>
            <td>${escapeHTML(p.product_name)}</td>
            <td>${escapeHTML(p.product_model)}</td>
            <td><span class="badge bg-light text-dark">${escapeHTML(p.product_type)}</span></td>
            <td><code>${escapeHTML(p.sn)}</code></td>
            <td>${escapeHTML(p.sn_code) || '-'}</td>
            <td>
                <button class="btn btn-sm btn-outline-black me-2" onclick="editProduct(${p.id})">修改</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${p.id})">删除</button>
            </td>
        </tr>
    `).join('');
    
    // 重置全选状态
    document.getElementById('selectAll').checked = false;
}

function renderPagination() {
    const totalPages = Math.ceil(totalProducts / pageSize);
    const info = document.getElementById('paginationInfo');
    const list = document.getElementById('pagination');
    
    info.innerText = `共 ${totalProducts} 条数据，当前第 ${currentPage} / ${totalPages || 1} 页`;
    
    let html = '';
    // 上一页
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadProducts(${currentPage - 1}); return false;">上一页</a>
    </li>`;
    
    // 页码 (简单处理，显示当前页附近的页码)
    let start = Math.max(1, currentPage - 2);
    let end = Math.min(totalPages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    
    for (let i = start; i <= end; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link ${i === currentPage ? 'bg-black border-black' : ''}" href="#" onclick="loadProducts(${i}); return false;">${i}</a>
        </li>`;
    }
    
    // 下一页
    html += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadProducts(${currentPage + 1}); return false;">下一页</a>
    </li>`;
    
    list.innerHTML = html;
}

function toggleSelectAll(checked) {
    document.querySelectorAll('.product-check').forEach(cb => cb.checked = checked);
}

function getSelectedProductIds() {
    return Array.from(document.querySelectorAll('.product-check:checked')).map(cb => cb.value);
}

async function batchDelete() {
    const ids = getSelectedProductIds();
    if (ids.length === 0) return alert('请先选择要删除的产品');
    
    if (!confirm(`确定要批量删除这 ${ids.length} 个产品吗？`)) return;
    
    try {
        const res = await fetch('./api/admin/batch_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        });
        const result = await res.json();
        if (res.ok) {
            alert('批量删除成功');
            loadProducts(currentPage);
        } else { alert('批量删除失败: ' + result.error); }
    } catch (e) { alert('批量删除出错'); }
}

function openBatchImport() {
    new bootstrap.Modal(document.getElementById('batchImportModal')).show();
}

async function doBatchImport() {
    const fileInput = document.getElementById('batchImportFile');
    if (!fileInput.files.length) return alert('请选择要导入的 Excel 文件');
    
    const file = fileInput.files[0];
    const reader = new FileReader();
    
    document.getElementById('importProgress').style.display = 'block';
    
    reader.onload = async (e) => {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            
            // 将 Excel 转换为 JSON
            const jsonData = XLSX.utils.sheet_to_json(worksheet);
            
            if (jsonData.length === 0) {
                throw new Error('Excel 文件中没有有效数据');
            }
            
            // 映射表头到后端字段
            // 映射表头 (中文 -> 英文)
            const products = jsonData.map(row => {
                return {
                    product_name: row['产品名称'] || row['Name'],
                    product_model: row['产品型号'] || row['Model'],
                    product_type: row['产品类型'] || row['Type'],
                    sn_code: row['物理SN'] || row['SN'],
                    sales_channel: row['销售渠道'] || '',
                    manual_link: row['手册链接'] || '',
                    remarks: row['备注'] || ''
                };
            });
            
            const res = await fetch('./api/admin/batch_add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ products: products })
            });
            
            const result = await res.json();
            if (res.ok) {
                alert(`成功导入 ${result.count} 条产品数据`);
                bootstrap.Modal.getInstance(document.getElementById('batchImportModal')).hide();
                loadProducts(1);
            } else { 
                alert('导入失败: ' + result.error + (result.details ? '\n' + result.details.join('\n') : '')); 
            }
        } catch (err) { 
            console.error(err);
            alert('文件解析或导入失败: ' + err.message); 
        } finally {
            document.getElementById('importProgress').style.display = 'none';
            fileInput.value = ''; // 清空选择，方便下次操作
        }
    };
    reader.readAsArrayBuffer(file);
}

// 辅助函数：处理 Excel 日期格式 (Excel 有时会把日期存为数字)
function formatExcelDate(date) {
    if (!date) return new Date().toISOString().split('T')[0];
    if (typeof date === 'number') {
        const d = XLSX.utils.format_cell({ v: date, t: 'd' });
        // 这里可能需要进一步转换，或者简单点：
        const excelEpoch = new Date(1899, 11, 30);
        const jsDate = new Date(excelEpoch.getTime() + date * 86400000);
        return jsDate.toISOString().split('T')[0];
    }
    // 如果是字符串，直接返回
    return date;
}

function openBatchEdit() {
    const ids = getSelectedProductIds();
    if (ids.length === 0) return alert('请先选择要修改的产品');
    
    document.getElementById('selectedCount').innerText = ids.length;
    document.getElementById('batchEditField').value = '';
    document.getElementById('batchEditValueContainer').style.display = 'none';
    new bootstrap.Modal(document.getElementById('batchEditModal')).show();
}

function onBatchEditFieldChange(field) {
    const container = document.getElementById('batchEditValueContainer');
    const inputArea = document.getElementById('batchEditValueInput');
    
    if (!field) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    if (field === 'product_type') {
        inputArea.innerHTML = `<select id="batchEditValue" class="form-select">
            ${productTypes.map(t => `<option value="${t.type_name}">${t.type_name}</option>`).join('')}
        </select>`;
    } else {
        inputArea.innerHTML = `<input type="text" id="batchEditValue" class="form-control" placeholder="请输入新内容">`;
    }
}

async function doBatchEdit() {
    const ids = getSelectedProductIds();
    const field = document.getElementById('batchEditField').value;
    const value = document.getElementById('batchEditValue').value;
    
    if (!field || !value) return alert('请填写要修改的字段和新值');
    
    try {
        const fields = {};
        fields[field] = value;
        
        const res = await fetch('./api/admin/batch_edit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids, fields: fields })
        });
        const result = await res.json();
        if (res.ok) {
            alert('批量修改成功');
            bootstrap.Modal.getInstance(document.getElementById('batchEditModal')).hide();
            loadProducts(currentPage);
        } else { alert('批量修改失败: ' + result.error); }
    } catch (e) { alert('批量修改出错'); }
}

// --- Export Excel Logic ---
function exportToExcel(data, fileName) {
    if (!data || data.length === 0) {
        alert('没有可导出的数据');
        return;
    }

    // 映射字段名为中文表头
    const exportData = data.map(p => ({
        '产品名称': p.product_name,
        '产品型号': p.product_model,
        '产品类型': p.product_type,
        '序列号': p.sn,
        '物理SN': p.sn_code || '',
        '销售渠道': p.sales_channel || '',
        '手册链接': p.manual_link || '',
        '备注': p.remarks || ''
    }));

    const worksheet = XLSX.utils.json_to_sheet(exportData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Products");
    XLSX.writeFile(workbook, `${fileName}_${new Date().getTime()}.xlsx`);
}

function exportCheckedProducts() {
    const ids = getSelectedProductIds();
    if (ids.length === 0) return alert('请先选择要导出的产品');

    const selectedProducts = products.filter(p => ids.includes(p.id.toString()));
    exportToExcel(selectedProducts, 'Selected_Products');
}

async function exportAllProducts() {
    if (!confirm('确定要导出所有产品数据吗？')) return;

    try {
        // 获取所有产品（不分页）
        const res = await fetch('./api/admin/list.php?page=1&limit=999999');
        const data = await res.json();
        if (res.ok && data.products) {
            exportToExcel(data.products, 'All_Products');
        } else {
            alert('获取数据失败');
        }
    } catch (e) {
        console.error(e);
        alert('导出出错');
    }
}

function resetProductForm() {
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('pImagePath').value = '';
    document.getElementById('productModalTitle').innerText = '添加新产品';
    document.getElementById('imagePreviewContainer').style.display = 'none';
    document.getElementById('uploadStatus').innerText = '';
    
    // 刷新类型下拉框
    const typeSelect = document.getElementById('pType');
    typeSelect.innerHTML = productTypes.map(t => `<option value="${t.type_name}">${t.type_name} (${t.sn_prefix})</option>`).join('');
    
    // 刷新模板下拉框
    const templateSelect = document.getElementById('pTemplateApply');
    templateSelect.innerHTML = '<option value="">-- 请选择模板 --</option>' + 
        productTemplates.map(t => `<option value="${t.id}">${t.template_name}</option>`).join('');
}

async function uploadImage() {
    const fileInput = document.getElementById('pImageFile');
    const status = document.getElementById('uploadStatus');
    if (!fileInput.files.length) return;
    status.innerText = '上传中...';
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    try {
        const res = await fetch('./api/admin/upload.php', { method: 'POST', body: formData });
        const result = await res.json();
        if (res.ok) {
            document.getElementById('pImagePath').value = result.path;
            status.innerText = '上传成功';
            status.className = 'form-text text-success';
        } else {
            status.innerText = '上传失败: ' + result.error;
            status.className = 'form-text text-danger';
        }
    } catch (e) { status.innerText = '上传出错'; status.className = 'form-text text-danger'; }
}

async function saveProduct() {
    const id = document.getElementById('productId').value;
    const data = {
        id: id,
        sn_code: document.getElementById('pSnCode').value,
        product_name: document.getElementById('pName').value,
        product_model: document.getElementById('pModel').value,
        product_type: document.getElementById('pType').value,
        sales_channel: document.getElementById('pSalesChannel').value,
        manual_link: document.getElementById('pManualLink').value,
        remarks: document.getElementById('pRemarks').value,
        image: document.getElementById('pImagePath').value
    };
    const url = id ? './api/admin/edit.php' : './api/admin/add.php';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok) {
            alert(id ? '修改成功' : '添加成功，序列号: ' + result.sn);
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
            loadProducts();
        } else { alert('保存失败: ' + result.error); }
    } catch (e) { alert('保存出错'); }
}

function editProduct(id) {
    const p = products.find(i => i.id == id);
    if (!p) return;
    
    resetProductForm(); // 确保下拉框已加载
    
    document.getElementById('productId').value = p.id;
    document.getElementById('pSnCode').value = p.sn_code || '';
    document.getElementById('pName').value = p.product_name;
    document.getElementById('pModel').value = p.product_model;
    document.getElementById('pType').value = p.product_type;
    document.getElementById('pSalesChannel').value = p.sales_channel || '';
    document.getElementById('pManualLink').value = p.manual_link || '';
    document.getElementById('pRemarks').value = p.remarks || '';
    document.getElementById('pImagePath').value = p.image || '';
    
    if (p.image_url) {
        document.getElementById('imagePreview').src = p.image_url;
        document.getElementById('imagePreviewContainer').style.display = 'block';
    }
    
    document.getElementById('productModalTitle').innerText = '修改产品';
    new bootstrap.Modal(document.getElementById('productModal')).show();
}

// --- Template Logic ---
async function loadTemplates() {
    try {
        const res = await fetch('./api/admin/template_list.php');
        productTemplates = await res.json();
        renderTemplates();
    } catch (e) { console.error(e); }
}

function renderTemplates() {
    const list = document.getElementById('templateList');
    list.innerHTML = productTemplates.map(t => `
        <tr>
            <td>${escapeHTML(t.template_name)}</td>
            <td>${escapeHTML(t.product_name)}</td>
            <td>${escapeHTML(t.product_model)}</td>
            <td><span class="badge bg-light text-dark">${escapeHTML(t.product_type)}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(${t.id})">删除</button>
            </td>
        </tr>
    `).join('');
}

function applyTemplate(id) {
    if (!id) return;
    const t = productTemplates.find(item => item.id == id);
    if (!t) return;
    
    document.getElementById('pName').value = t.product_name;
    document.getElementById('pModel').value = t.product_model;
    document.getElementById('pType').value = t.product_type;
    document.getElementById('pSalesChannel').value = t.sales_channel || '';
    document.getElementById('pManualLink').value = t.manual_link || '';
    document.getElementById('pRemarks').value = t.remarks || '';
    document.getElementById('pImagePath').value = t.image || '';
    
    if (t.image) {
        // 需要一个预览逻辑
        document.getElementById('imagePreview').src = './' + t.image;
        document.getElementById('imagePreviewContainer').style.display = 'block';
    }
}

async function saveAsTemplate() {
    const name = prompt('请输入模板名称:');
    if (!name) return;

    const data = {
        template_name: name,
        product_type: document.getElementById('pType').value,
        product_name: document.getElementById('pName').value,
        product_model: document.getElementById('pModel').value,
        sales_channel: document.getElementById('pSalesChannel').value,
        manual_link: document.getElementById('pManualLink').value,
        image: document.getElementById('pImagePath').value,
        remarks: document.getElementById('pRemarks').value
    };

    try {
        const res = await fetch('./api/admin/template_add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (res.ok) {
            alert('模板保存成功');
            loadTemplates();
            // 刷新下拉框
            const templateSelect = document.getElementById('pTemplateApply');
            templateSelect.innerHTML = '<option value="">-- 请选择模板 --</option>' + 
                productTemplates.map(t => `<option value="${t.id}">${t.template_name}</option>`).join('');
        }
    } catch (e) { alert('保存模板失败'); }
}

async function deleteTemplate(id) {
    if (!confirm('确定删除该模板？')) return;
    try {
        const res = await fetch('./api/admin/template_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        if (res.ok) loadTemplates();
    } catch (e) { alert('删除模板失败'); }
}

// --- Gallery Logic ---
async function loadGallery() {
    try {
        const res = await fetch('./api/admin/gallery.php');
        galleryImages = await res.json();
        renderGallery();
    } catch (e) { console.error(e); }
}

function renderGallery() {
    const list = document.getElementById('galleryList');
    if (galleryImages.length === 0) {
        list.innerHTML = '<div class="col-12 text-center text-muted p-5">图库暂无图片，请先上传</div>';
        return;
    }
    list.innerHTML = galleryImages.map(img => `
        <div class="col-3 text-center">
            <div class="gallery-item border rounded p-1" onclick="selectFromGallery('${escapeHTML(img.path)}', '${escapeHTML(img.url)}'); return false;" style="cursor:pointer;">
                <img src="${escapeHTML(img.url)}" class="img-fluid rounded" style="width:80px; height:80px; object-fit:cover;">
                <div class="small text-truncate mt-1" style="font-size: 0.7rem;">${escapeHTML(img.name)}</div>
            </div>
        </div>
    `).join('');
}

function selectFromGallery(path, url) {
    document.getElementById('pImagePath').value = path;
    document.getElementById('imagePreview').src = url;
    document.getElementById('imagePreviewContainer').style.display = 'block';
    document.getElementById('uploadStatus').innerText = '已从图库选择';
    
    const galleryEl = document.getElementById('galleryModal');
    const modal = bootstrap.Modal.getInstance(galleryEl) || new bootstrap.Modal(galleryEl);
    modal.hide();
    return false;
}

function openGallery() {
    const galleryEl = document.getElementById('galleryModal');
    const galleryModal = bootstrap.Modal.getOrCreateInstance(galleryEl, {
        backdrop: false
    });
    galleryModal.show();
}

// --- Product Types Logic ---
async function loadTypes() {
    try {
        const res = await fetch('./api/admin/type_list.php');
        productTypes = await res.json();
        renderTypes();
    } catch (e) { console.error(e); }
}

function renderTypes() {
    const list = document.getElementById('typeList');
    list.innerHTML = productTypes.map(t => `
        <tr>
            <td>${t.id}</td>
            <td>${escapeHTML(t.type_name)}</td>
            <td><code>${escapeHTML(t.sn_prefix)}</code></td>
            <td>${escapeHTML(t.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-outline-black me-2" onclick="editType(${t.id})">修改</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteType(${t.id})">删除</button>
            </td>
        </tr>
    `).join('');

    // 同时也更新筛选下拉框
    const filterType = document.getElementById('filterType');
    const currentVal = filterType.value;
    filterType.innerHTML = '<option value="">所有类型</option>' + 
        productTypes.map(t => `<option value="${escapeHTML(t.type_name)}">${escapeHTML(t.type_name)}</option>`).join('');
    filterType.value = currentVal;
}

function resetTypeForm() {
    document.getElementById('typeForm').reset();
    document.getElementById('typeId').value = '';
    document.getElementById('typeModalTitle').innerText = '添加产品类型';
}

async function saveType() {
    const id = document.getElementById('typeId').value;
    const data = {
        id: id,
        type_name: document.getElementById('typeName').value,
        sn_prefix: document.getElementById('typePrefix').value.toUpperCase()
    };
    const url = id ? './api/admin/type_edit.php' : './api/admin/type_add.php';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (res.ok) {
            bootstrap.Modal.getInstance(document.getElementById('typeModal')).hide();
            loadTypes();
        } else { const result = await res.json(); alert('失败: ' + result.error); }
    } catch (e) { alert('出错'); }
}

function editType(id) {
    const t = productTypes.find(i => i.id == id);
    if (!t) return;
    document.getElementById('typeId').value = t.id;
    document.getElementById('typeName').value = t.type_name;
    document.getElementById('typePrefix').value = t.sn_prefix;
    document.getElementById('typeModalTitle').innerText = '修改产品类型';
    new bootstrap.Modal(document.getElementById('typeModal')).show();
}

async function deleteType(id) {
    if (!confirm('确定删除该类型吗？如果已有产品使用此类型将无法删除。')) return;
    try {
        const res = await fetch('./api/admin/type_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        if (res.ok) loadTypes();
        else { const result = await res.json(); alert('失败: ' + result.error); }
    } catch (e) { alert('出错'); }
}

async function deleteProduct(id) {
    if (!confirm('确定删除？')) return;
    try {
        const res = await fetch('./api/admin/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        if (res.ok) loadProducts();
        else { const result = await res.json(); alert('失败: ' + result.error); }
    } catch (e) { alert('出错'); }
}

// --- Admins Logic ---
async function loadAdmins() {
    try {
        const res = await fetch('./api/admin/admin_list.php');
        admins = await res.json();
        renderAdmins();
    } catch (e) { console.error(e); }
}

function renderAdmins() {
    const list = document.getElementById('adminList');
    const isAdmin = currentAdminUsername === 'admin';
    
    list.innerHTML = admins.map(a => `
        <tr>
            <td>${a.id}</td>
            <td>${escapeHTML(a.username)}</td>
            <td>${escapeHTML(a.created_at)}</td>
            ${isAdmin ? `
            <td>
                <button class="btn btn-sm btn-outline-black me-2" onclick="editAdmin(${a.id})">修改</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteAdmin(${a.id})">删除</button>
            </td>
            ` : '<td class="admin-only-cell" style="display:none;"></td>'}
        </tr>
    `).join('');
}

function resetAdminForm() {
    document.getElementById('adminForm').reset();
    document.getElementById('adminId').value = '';
    document.getElementById('adminModalTitle').innerText = '添加管理员';
    document.getElementById('adminPassword').placeholder = '请输入密码';
}

async function saveAdmin() {
    const id = document.getElementById('adminId').value;
    const data = {
        id: id,
        username: document.getElementById('adminUsername').value,
        password: document.getElementById('adminPassword').value
    };
    const url = id ? './api/admin/admin_edit.php' : './api/admin/admin_add.php';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok) {
            alert('保存成功');
            bootstrap.Modal.getInstance(document.getElementById('adminModal')).hide();
            loadAdmins();
        } else { alert('失败: ' + result.error); }
    } catch (e) { alert('出错'); }
}

function editAdmin(id) {
    const a = admins.find(i => i.id == id);
    if (!a) return;
    document.getElementById('adminId').value = a.id;
    document.getElementById('adminUsername').value = a.username;
    document.getElementById('adminPassword').value = '';
    document.getElementById('adminPassword').placeholder = '留空则不修改';
    document.getElementById('adminModalTitle').innerText = '修改管理员';
    new bootstrap.Modal(document.getElementById('adminModal')).show();
}

async function deleteAdmin(id) {
    if (!confirm('确定删除该管理员吗？')) return;
    try {
        const res = await fetch('./api/admin/admin_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await res.json();
        if (res.ok) loadAdmins();
        else alert('失败: ' + result.error);
    } catch (e) { alert('出错'); }
}

// --- Logs Logic ---
async function loadLogs(page = 1) {
    currentLogPage = page;
    try {
        const res = await fetch(`./api/admin/log_list.php?page=${currentLogPage}&limit=${logPageSize}`);
        const data = await res.json();
        if (res.ok) {
            systemLogs = data.logs;
            totalLogs = data.total;
            
            // 更新配置 UI (如果是 admin)
            if (currentAdminUsername === 'admin') {
                document.getElementById('logEnabledSwitch').checked = data.configs.log_enabled === '1';
                document.getElementById('logRetentionInput').value = data.configs.log_retention_days;
            }
            
            renderLogs();
            renderLogPagination();
        }
    } catch (e) { console.error(e); }
}

function renderLogs() {
    const list = document.getElementById('logList');
    if (systemLogs.length === 0) {
        list.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">暂无日志记录</td></tr>';
        return;
    }
    list.innerHTML = systemLogs.map(log => `
        <tr>
            <td><small class="text-muted">${escapeHTML(log.created_at)}</small></td>
            <td><span class="badge bg-light text-dark">${escapeHTML(log.username)}</span></td>
            <td><span class="fw-bold">${escapeHTML(log.action)}</span></td>
            <td><small>${escapeHTML(log.details || '-')}</small></td>
            <td><code>${escapeHTML(log.ip_address || '-')}</code></td>
        </tr>
    `).join('');
}

function renderLogPagination() {
    const totalPages = Math.ceil(totalLogs / logPageSize);
    const info = document.getElementById('logPaginationInfo');
    const list = document.getElementById('logPagination');
    
    info.innerText = `共 ${totalLogs} 条日志，当前第 ${currentLogPage} / ${totalPages || 1} 页`;
    
    let html = '';
    html += `<li class="page-item ${currentLogPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadLogs(${currentLogPage - 1}); return false;">上一页</a>
    </li>`;
    
    let start = Math.max(1, currentLogPage - 2);
    let end = Math.min(totalPages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    
    for (let i = start; i <= end; i++) {
        if (i < 1) continue;
        html += `<li class="page-item ${i === currentLogPage ? 'active' : ''}">
            <a class="page-link ${i === currentLogPage ? 'bg-black border-black' : ''}" href="#" onclick="loadLogs(${i}); return false;">${i}</a>
        </li>`;
    }
    
    html += `<li class="page-item ${currentLogPage >= totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadLogs(${currentLogPage + 1}); return false;">下一页</a>
    </li>`;
    
    list.innerHTML = html;
}

async function updateLogConfig() {
    if (currentAdminUsername !== 'admin') return;
    
    const enabled = document.getElementById('logEnabledSwitch').checked;
    const days = document.getElementById('logRetentionInput').value;
    
    try {
        const res = await fetch('./api/admin/log_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                log_enabled: enabled,
                log_retention_days: days
            })
        });
        if (res.ok) {
            // 配置更新成功，不需要弹窗，静默刷新即可
        } else {
            const result = await res.json();
            alert('配置更新失败: ' + result.error);
        }
    } catch (e) { alert('配置更新出错'); }
}

async function clearLogs() {
    if (currentAdminUsername !== 'admin') return;
    if (!confirm('确定要清空所有系统日志吗？此操作不可恢复。')) return;
    
    try {
        const res = await fetch('./api/admin/log_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ clear_logs: true })
        });
        if (res.ok) {
            alert('日志已清空');
            loadLogs(1);
        } else {
            const result = await res.json();
            alert('清空失败: ' + result.error);
        }
    } catch (e) { alert('清空出错'); }
}

// --- Settings Logic ---
async function loadSettings() {
    try {
        const res = await fetch('./api/admin/settings.php');
        if (!res.ok) return;
        const data = await res.json();
        document.getElementById('settingSiteName').value = data.site_name || '';
        document.getElementById('settingIcpNo').value = data.icp_no || '';
        document.getElementById('settingGonganNo').value = data.gongan_no || '';
        document.getElementById('settingCopyright').value = data.copyright_text || '';
        document.getElementById('settingSnGroups').value = data.sn_groups || '3';
        document.getElementById('settingSnCharsPerGroup').value = data.sn_chars_per_group || '4';
        const postApiEnabled = data.post_api_enabled !== undefined ? data.post_api_enabled : '1';
        const postApiCheckbox = document.getElementById('settingPostApiEnabled');
        if (postApiCheckbox) {
            postApiCheckbox.checked = postApiEnabled === '1';
            togglePostApiInfo();
        }
        const apiTokenInput = document.getElementById('settingApiToken');
        if (apiTokenInput && data.api_token !== undefined) {
            apiTokenInput.value = data.api_token || '';
        }
        const factoryApiEnabled = data.factory_api_enabled !== undefined ? data.factory_api_enabled : '0';
        const factoryApiCheckbox = document.getElementById('settingFactoryApiEnabled');
        if (factoryApiCheckbox) {
            factoryApiCheckbox.checked = factoryApiEnabled === '1';
            toggleFactoryApiInfo();
        }
        const factoryApiTokenInput = document.getElementById('settingFactoryApiToken');
        if (factoryApiTokenInput && data.factory_api_token !== undefined) {
            factoryApiTokenInput.value = data.factory_api_token || '';
        }
        updateSnPreview();
    } catch (e) { /* 设置加载失败 */ }
}

function updateSnPreview() {
    const groups = parseInt(document.getElementById('settingSnGroups').value) || 3;
    const chars = parseInt(document.getElementById('settingSnCharsPerGroup').value) || 4;
    const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    const parts = [];
    for (let g = 0; g < groups; g++) {
        let part = '';
        for (let c = 0; c < chars; c++) {
            part += charset[Math.floor(Math.random() * charset.length)];
        }
        parts.push(part);
    }
    document.getElementById('snPreview').textContent = parts.join('-');
}

async function saveSystemSettings() {
    const statusEl = document.getElementById('settingsStatus');
    try {
        const body = {
            site_name: document.getElementById('settingSiteName').value.trim(),
            icp_no: document.getElementById('settingIcpNo').value.trim(),
            gongan_no: document.getElementById('settingGonganNo').value.trim(),
            copyright_text: document.getElementById('settingCopyright').value.trim(),
            sn_groups: document.getElementById('settingSnGroups').value,
            sn_chars_per_group: document.getElementById('settingSnCharsPerGroup').value,
            post_api_enabled: document.getElementById('settingPostApiEnabled').checked ? '1' : '0',
            api_token: (document.getElementById('settingApiToken') || {}).value || '',
            factory_api_enabled: document.getElementById('settingFactoryApiEnabled').checked ? '1' : '0',
            factory_api_token: (document.getElementById('settingFactoryApiToken') || {}).value || ''
        };
        const res = await fetch('./api/admin/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (res.ok && data.success) {
            statusEl.style.display = 'block';
            statusEl.className = 'mt-2 small text-success';
            statusEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>设置保存成功';
            setTimeout(() => { statusEl.style.display = 'none'; }, 3000);
        } else {
            statusEl.style.display = 'block';
            statusEl.className = 'mt-2 small text-danger';
            statusEl.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' + (data.error || '保存失败');
        }
    } catch (e) {
        statusEl.style.display = 'block';
        statusEl.className = 'mt-2 small text-danger';
        statusEl.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>网络错误';
    }
}

async function refreshAllSn() {
    if (!confirm('确定要刷新所有产品的序列号吗？此操作将重新生成所有产品的序列号。')) return;
    
    const statusEl = document.getElementById('settingsStatus');
    try {
        statusEl.style.display = 'block';
        statusEl.className = 'mt-2 small text-primary';
        statusEl.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>正在刷新序列号...';
        
        const res = await fetch('./api/admin/refresh_sn.php', { method: 'POST' });
        const data = await res.json();
        if (res.ok && data.success) {
            statusEl.className = 'mt-2 small text-success';
            statusEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + data.message;
            // 重新加载产品列表以显示新的序列号
            if (typeof loadProducts === 'function') loadProducts(currentPage);
        } else {
            statusEl.className = 'mt-2 small text-danger';
            statusEl.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' + (data.error || '刷新失败');
        }
    } catch (e) {
        statusEl.style.display = 'block';
        statusEl.className = 'mt-2 small text-danger';
        statusEl.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>网络错误';
    }
}

// POST API 信息区域展开/收起
function togglePostApiInfo() {
    const checkbox = document.getElementById('settingPostApiEnabled');
    const info = document.getElementById('postApiInfo');
    if (info) {
        info.style.display = checkbox && checkbox.checked ? 'block' : 'none';
    }
}

// 随机生成强口令
function generateApiToken() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    const length = 32;
    let token = '';
    const arr = new Uint32Array(length);
    crypto.getRandomValues(arr);
    for (let i = 0; i < length; i++) {
        token += chars[arr[i] % chars.length];
    }
    document.getElementById('settingApiToken').value = token;
}

// 清空口令（允许无口令访问）
function clearApiToken() {
    document.getElementById('settingApiToken').value = '';
}

// --- Factory API ---
function toggleFactoryApiInfo() {
    const checkbox = document.getElementById('settingFactoryApiEnabled');
    const info = document.getElementById('factoryApiInfo');
    if (info) {
        info.style.display = checkbox && checkbox.checked ? 'block' : 'none';
    }
}

function generateFactoryApiToken() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    const length = 32;
    let token = '';
    const arr = new Uint32Array(length);
    crypto.getRandomValues(arr);
    for (let i = 0; i < length; i++) {
        token += chars[arr[i] % chars.length];
    }
    document.getElementById('settingFactoryApiToken').value = token;
}

function clearFactoryApiToken() {
    document.getElementById('settingFactoryApiToken').value = '';
}

// 监听序列号规则输入变化，更新预览
document.addEventListener('DOMContentLoaded', function() {
    const groupsInput = document.getElementById('settingSnGroups');
    const charsInput = document.getElementById('settingSnCharsPerGroup');
    if (groupsInput) groupsInput.addEventListener('input', updateSnPreview);
    if (charsInput) charsInput.addEventListener('input', updateSnPreview);
});
