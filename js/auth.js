
async function checkAuth() {
    const storedUser = localStorage.getItem('user');
    if (!storedUser) {
        window.location.href = 'login.html';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const protectedPages = ['profile.html', 'test.html'];
    const currentPath = window.location.pathname.split('/').pop();

    if (protectedPages.includes(currentPath)) {
        checkAuth();
    }
});