// 加载站点配置（ICP/公安备案）
(async function() {
    try {
        const res = await fetch('./api/site_config.php');
        const data = await res.json();
        const beianEl = document.getElementById('footerBeian');
        const copyrightEl = document.getElementById('footerCopyright');
        if (copyrightEl && data.copyright_text) {
            copyrightEl.textContent = data.copyright_text;
        }
        if (!beianEl) return;
        let html = '';
        if (data.icp_no) html += `<a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener" style="color:#999;text-decoration:none;">${data.icp_no}</a>`;
        if (data.gongan_no) html += (html ? ' &nbsp; ' : '') + `<a href="https://beian.mps.gov.cn/#/query/webSearch" target="_blank" rel="noopener" style="color:#999;text-decoration:none;">${data.gongan_no}</a>`;
        if (html) beianEl.innerHTML = html;
    } catch(e) {}
})();

async function queryProduct() {
    const sn = document.getElementById('snInput').value.trim();
    if (!sn) {
        alert('请输入序列号');
        return;
    }

    const loading = document.getElementById('loading');
    const resultCard = document.getElementById('resultCard');
    const errorMsg = document.getElementById('errorMsg');

    loading.style.display = 'block';
    resultCard.style.display = 'none';
    errorMsg.style.display = 'none';

    try {
        const response = await fetch(`./api/query.php?sn=${encodeURIComponent(sn)}&_t=${Date.now()}`);
        let data;

        try {
            data = await response.json();
        } catch (e) {
            data = { error: '您输入的序列号不正确或非正品' };
        }

        loading.style.display = 'none';

        if (response.ok) {
            document.getElementById('pName').innerText = data.product_name;
            document.getElementById('pModel').innerText = data.product_model;
            document.getElementById('pType').innerText = data.product_type;
            document.getElementById('pSalesChannel').innerText = data.sales_channel || '官方渠道';
            document.getElementById('pSn').innerText = data.sn;
            document.getElementById('pSnCode').innerText = data.sn_code || '未绑定';
            document.getElementById('pQueryCount').innerText = data.query_count !== undefined ? data.query_count : '-';
            if (data.manual_link) {
                document.getElementById('pManualLink').href = data.manual_link;
                document.getElementById('pManual').style.display = 'block';
            } else {
                document.getElementById('pManual').style.display = 'none';
            }

            document.getElementById('pRemarks').innerText = data.remarks || '无';
            document.getElementById('pImg').src = data.image_url || 'https://via.placeholder.com/400x300?text=No+Image';

            resultCard.style.display = 'block';
        } else {
            errorMsg.innerText = (data && data.error) ? data.error : '您输入的序列号不正确或非正品';
            errorMsg.style.display = 'block';
        }
    } catch (error) {
        loading.style.display = 'none';
        console.error('Fetch error:', error);
        errorMsg.innerText = '您输入的序列号不正确或非正品';
        errorMsg.style.display = 'block';
    }
}