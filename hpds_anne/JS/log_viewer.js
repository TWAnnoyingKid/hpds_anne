// 日誌查看器 JavaScript

class LogViewer {
    constructor() {
        this.currentLogs = [];
        this.currentFiles = [];
        this.init();
    }
    
    init() {
        this.loadLogFiles();
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // 搜尋輸入框 Enter 鍵支援
        document.getElementById('searchKeyword').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchLogs();
            }
        });
    }
    
    async loadLogFiles() {
        try {
            const response = await fetch(API_CONFIG.getUrl('logViewer') + '?action=list');
            const data = await response.json();
            
            if (data.success) {
                this.currentFiles = data.files;
                this.populateFileSelect();
                this.displayFileList();
            } else {
                this.showError('載入日誌檔案失敗: ' + (data.error || '未知錯誤'));
            }
        } catch (error) {
            this.showError('載入日誌檔案時發生錯誤: ' + error.message);
        }
    }
    
    populateFileSelect() {
        const select = document.getElementById('selectedFile');
        select.innerHTML = '<option value="">選擇日誌檔案...</option>';
        
        this.currentFiles.forEach(file => {
            const option = document.createElement('option');
            option.value = file.name;
            option.textContent = `${file.name} (${this.formatFileSize(file.size)})`;
            select.appendChild(option);
        });
    }
    
    displayFileList() {
        const logDisplay = document.getElementById('logDisplay');
        
        if (this.currentFiles.length === 0) {
            logDisplay.innerHTML = `
                <div class="text-center p-4">
                    <i class="bi bi-file-earmark-x-fill text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">沒有找到日誌檔案</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="p-3 border-bottom bg-light">
                <h5 class="mb-1">日誌檔案列表</h5>
                <small class="text-muted">共有 ${this.currentFiles.length} 個日誌檔案</small>
            </div>
            <div class="p-3">
                <div class="row g-3">
        `;
        
        this.currentFiles.forEach(file => {
            html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-truncate">${file.name}</h6>
                            <p class="card-text small text-muted">
                                <i class="bi bi-file-earmark"></i> ${this.formatFileSize(file.size)}<br>
                                <i class="bi bi-clock"></i> ${new Date(file.modified * 1000).toLocaleString('zh-TW')}
                            </p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary btn-sm" onclick="logViewer.viewFile('${file.name}')">
                                    查看
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="logViewer.downloadFile('${file.name}')">
                                    下載
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        logDisplay.innerHTML = html;
    }
    
    async viewFile(filename) {
        this.showLoading('正在載入日誌內容...');
        
        try {
            const response = await fetch(`${API_CONFIG.getUrl('logViewer')}?action=read&filename=${encodeURIComponent(filename)}&lines=200`);
            const data = await response.json();
            
            if (data.success) {
                this.currentLogs = data.logs;
                this.displayLogs(data.logs, `日誌檔案: ${filename}`);
            } else {
                this.showError('載入日誌內容失敗: ' + (data.error || '未知錯誤'));
            }
        } catch (error) {
            this.showError('載入日誌內容時發生錯誤: ' + error.message);
        }
    }
    
    viewSelectedFile() {
        const selectedFile = document.getElementById('selectedFile').value;
        if (!selectedFile) {
            alert('請選擇一個日誌檔案');
            return;
        }
        this.viewFile(selectedFile);
    }
    
    downloadFile(filename) {
        window.open(`${API_CONFIG.getUrl('logViewer')}?action=download&filename=${encodeURIComponent(filename)}`);
    }
    
    downloadSelectedFile() {
        const selectedFile = document.getElementById('selectedFile').value;
        if (!selectedFile) {
            alert('請選擇一個日誌檔案');
            return;
        }
        this.downloadFile(selectedFile);
    }
    
    async searchLogs() {
        const keyword = document.getElementById('searchKeyword').value.trim();
        const level = document.getElementById('searchLevel').value;
        const category = document.getElementById('searchCategory').value;
        const filename = document.getElementById('selectedFile').value;
        
        if (!keyword && !level && !category) {
            alert('請至少輸入一個搜尋條件');
            return;
        }
        
        this.showLoading('正在搜尋日誌...');
        
        try {
            const params = new URLSearchParams({
                action: 'search',
                keyword: keyword,
                level: level,
                category: category,
                filename: filename,
                lines: '500'
            });
            
            const response = await fetch(`${API_CONFIG.getUrl('logViewer')}?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.currentLogs = data.results;
                this.displayLogs(data.results, '搜尋結果');
            } else {
                this.showError('搜尋失敗: ' + (data.error || '未知錯誤'));
            }
        } catch (error) {
            this.showError('搜尋時發生錯誤: ' + error.message);
        }
    }
    
    displayLogs(logs, title) {
        const logDisplay = document.getElementById('logDisplay');
        
        if (!logs || logs.length === 0) {
            logDisplay.innerHTML = `
                <div class="p-3 border-bottom bg-light">
                    <h5 class="mb-1">${title}</h5>
                    <small class="text-muted">搜尋完成</small>
                </div>
                <div class="text-center p-5">
                    <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">沒有找到符合條件的日誌記錄</h5>
                    <p class="text-muted">請嘗試調整搜尋條件</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="p-3 border-bottom bg-light">
                <h5 class="mb-1">${title}</h5>
                <small class="text-muted">共找到 ${logs.length} 筆記錄</small>
            </div>
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th style="width: 140px;">時間</th>
                            <th style="width: 80px;">等級</th>
                            <th style="width: 120px;">類別</th>
                            <th style="width: 100px;">用戶</th>
                            <th style="width: 100px;">IP</th>
                            <th>訊息</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        logs.forEach(log => {
            const levelBadge = this.getLevelBadge(log.level);
            html += `
                <tr>
                    <td><small>${this.formatTimestamp(log.timestamp)}</small></td>
                    <td>${levelBadge}</td>
                    <td><small>${this.translateCategory(log.category)}</small></td>
                    <td><code class="text-primary">${this.escapeHtml(log.user_id)}</code></td>
                    <td><small class="text-muted">${this.escapeHtml(log.ip)}</small></td>
                    <td style="max-width: 400px; word-break: break-word;">${this.escapeHtml(log.message)}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        logDisplay.innerHTML = html;
    }
    
    showLoading(message) {
        document.getElementById('logDisplay').innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">${message}</p>
            </div>
        `;
    }
    
    showError(message) {
        document.getElementById('logDisplay').innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                <h5 class="text-danger mt-2">發生錯誤</h5>
                <p class="text-muted">${message}</p>
            </div>
        `;
    }
    
    showSuccess(message) {
        // 簡潔的成功提示 - 使用 alert 確保相容性
        alert('✅ ' + message);
        
        // 同時在控制台記錄
        console.log('成功:', message);
    }
    
    getLevelBadge(level) {
        const badges = {
            'SUCCESS': '<span class="badge bg-success">成功</span>',
            'WARNING': '<span class="badge bg-warning text-dark">警告</span>',
            'ERROR': '<span class="badge bg-danger">錯誤</span>',
            'INFO': '<span class="badge bg-info text-dark">資訊</span>'
        };
        return badges[level] || `<span class="badge bg-secondary">${level}</span>`;
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString('zh-TW', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }
    
    translateLevel(level) {
        const translations = {
            'SUCCESS': '成功',
            'WARNING': '警告',
            'ERROR': '錯誤',
            'INFO': '資訊'
        };
        return translations[level] || level;
    }
    
    translateCategory(category) {
        const translations = {
            'LOGIN_SUCCESS': '登入成功',
            'LOGIN_FAILED': '登入失敗',
            'AUTO_LOGOUT': '自動登出',
            'USER_EXPORT': '用戶匯出',
            'SCORE_EXPORT': '成績匯出',
            'SCORE_IMPORT': '成績匯入',
            'UNITY_SCORE_IMPORT': 'Unity成績匯入',
            'USER_MANAGEMENT': '用戶管理',
            'SECURITY': '安全事件',
            'SYSTEM_ERROR': '系統錯誤',
            'PERMISSION_DENIED': '權限拒絕',
            'LOG_DOWNLOAD': '日誌下載'
        };
        return translations[category] || category;
    }
    
    escapeHtml(text) {
        // 使用 common.js 中的統一函數
        return escapeHtml(text);
    }
    
    // 清理舊日誌檔案
    async cleanupOldLogs(days = 30) {
        if (!confirm(`確定要刪除超過 ${days} 天的舊日誌檔案嗎？此操作無法復原。`)) {
            return;
        }
        
        try {
            const response = await fetch(API_CONFIG.getUrl('logViewer') + `?action=cleanup&days=${days}`);
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(`成功刪除 ${data.count} 個舊日誌檔案`);
                // 重新載入檔案列表
                await this.loadLogFiles();
            } else {
                this.showError('清理日誌失敗: ' + (data.error || '未知錯誤'));
            }
        } catch (error) {
            this.showError('清理日誌時發生錯誤: ' + error.message);
        }
    }
}

// 全域函式供 HTML 使用
function searchLogs() {
    logViewer.searchLogs();
}

function viewSelectedFile() {
    logViewer.viewSelectedFile();
}

function downloadSelectedFile() {
    logViewer.downloadSelectedFile();
}

function cleanupOldLogs() {
    logViewer.cleanupOldLogs();
}

// 初始化日誌查看器
const logViewer = new LogViewer();
