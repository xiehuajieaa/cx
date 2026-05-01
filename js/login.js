document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorMsg = document.getElementById('errorMsg');
    errorMsg.style.display = 'none';

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();

    try {
        const response = await fetch('./api/admin/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json();

        if (response.ok) {
            window.location.href = 'admin.html';
        } else {
            errorMsg.innerText = data.error || '登录失败';
            errorMsg.style.display = 'block';
        }
    } catch (error) {
        errorMsg.innerText = '服务器连接失败';
        errorMsg.style.display = 'block';
    }
});

// 检查是否已经登录
window.onload = async () => {
    try {
        const res = await fetch('./api/admin/check_auth.php');
        if (res.ok) window.location.href = 'admin.html';
    } catch (e) {}
};