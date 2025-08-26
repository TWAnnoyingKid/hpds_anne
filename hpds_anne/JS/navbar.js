// navbar.js - 統一導航欄管理

class NavbarManager {
    constructor() {
        this.currentPage = '';
        this.userPermission = '';
        this.userName = '';
        this.isPersonalPage = false;
    }

    // 初始化導航欄
    async init(options = {}) {
        this.currentPage = options.currentPage || '';
        this.isPersonalPage = options.isPersonalPage || false;
        
        // 載入導航欄 HTML
        await this.loadNavbarHTML();
        
        // 檢查用戶權限
        await this.checkUserAuth();
        
        // 生成導航項目
        this.generateNavItems();
        
        // 綁定事件
        this.bindEvents();
        
        // 設置當前頁面樣式
        this.setActivePage();
        
        // 更新用戶顯示
        this.updateUserDisplay();
    }

    // 載入導航欄 HTML
    async loadNavbarHTML() {
        try {
            const response = await fetch('navbar.html');
            const html = await response.text();
            $('body').prepend(html);
        } catch (error) {
            console.error('載入導航欄失敗:', error);
        }
    }

    // 檢查用戶權限
    async checkUserAuth() {
        return new Promise((resolve) => {
            $.post(API_CONFIG.getUrl('getData'), {}, (response) => {
                if (response.error) {
                    console.log('權限檢查失敗:', response.error);
                    location.href = 'index.html';
                } else {
                    this.userPermission = response.permission;
                    if (response.name) {
                        this.userName = decodeB64Utf8(response.name);
                    } else if (response.student_id) {
                        this.userName = response.student_id;
                    } else {
                        this.userName = "用戶";
                    }
                    resolve();
                }
            }, 'json').fail(() => {
                console.log('權限檢查失敗');
                location.href = 'index.html';
            });
        });
    }

    // 生成導航項目
    generateNavItems() {
        const navItems = $('#navItems');
        navItems.empty();

        // 根據權限決定顯示的導航項目
        const menuItems = this.getMenuItems();
        
        menuItems.forEach(item => {
            const li = $(`<li class="nav-item">
                <a class="nav-link" href="${item.href}">${item.text}</a>
            </li>`);
            navItems.append(li);
        });

        // 檢查系統管理員權限並顯示系統日誌
        this.checkSystemAccess();
    }

    // 根據權限獲取菜單項目
    getMenuItems() {
        if (this.isPersonalPage) {
            // 個人頁面不顯示其他導航項目
            return [];
        }

        // 管理員權限菜單
        if (['teacher', 'ta'].includes(this.userPermission)) {
            return [
                { href: 'manage.html', text: '用戶管理' },
                { href: 'search.html', text: '數據查詢' },
                { href: 'all_score.html', text: '所有成績' }
            ];
        }

        // 一般用戶菜單（護理師、護生等）
        return [
            { href: 'score.html', text: '我的成績' }
        ];
    }

    // 設置當前頁面的 active 狀態
    setActivePage() {
        $('#navItems .nav-link').removeClass('active');
        
        if (this.currentPage) {
            $(`#navItems a[href="${this.currentPage}"]`).addClass('active');
        }
    }

    // 更新用戶顯示
    updateUserDisplay() {
        if (this.isPersonalPage) {
            $('#currentUser').text(`歡迎，${this.userName}`).show();
            $('#editPersonalInfoBtn').show();
        } else {
            $('#currentUser').hide();
            $('#editPersonalInfoBtn').hide();
        }
    }

    // 檢查系統管理員權限
    checkSystemAccess() {
        if (!this.isPersonalPage && ['teacher', 'ta'].includes(this.userPermission)) {
            // 所有 teacher 和 ta 權限都可以查看系統日誌
            $('#navItems').append(`
                <li class="nav-item">
                    <a class="nav-link" href="log_viewer.html">系統日誌</a>
                </li>
            `);
            this.setActivePage(); // 重新設置 active 狀態
        }
    }

    // 綁定事件
    bindEvents() {
        // 登出按鈕
        $('#logoutBtn').off('click').on('click', () => {
            $.post(API_CONFIG.getUrl('getData'), { action: 'logout' }, () => {
                location.href = 'index.html';
            }).fail(() => {
                location.href = 'index.html';
            });
        });

        // 個人資訊編輯按鈕（如果是個人頁面）
        if (this.isPersonalPage) {
            $('#editPersonalInfoBtn').off('click').on('click', () => {
                // 觸發個人資訊編輯事件
                $(document).trigger('showPersonalInfoModal');
            });
        }
    }

    // 靜態方法：快速初始化
    static async init(options = {}) {
        const navbar = new NavbarManager();
        await navbar.init(options);
        return navbar;
    }
}

// 全域函數，向後兼容
window.initNavbar = NavbarManager.init;

function checkSystemAccess() {
    // 這個函數現在由 NavbarManager 處理
    return Promise.resolve();
}
