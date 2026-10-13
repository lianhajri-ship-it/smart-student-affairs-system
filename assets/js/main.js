// ===================================================
// نظام عمادة شؤون الطلاب - JavaScript الرئيسي
// ===================================================

document.addEventListener('DOMContentLoaded', function () {

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Confirm delete dialogs
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'هل أنت متأكد؟';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // Auto-resize textareas
    document.querySelectorAll('textarea.auto-resize').forEach(function (ta) {
        ta.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // Search filter for tables
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // File input preview
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            if (preview && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Mark notifications as read
    document.querySelectorAll('.notif-item.unread').forEach(function (item) {
        item.addEventListener('click', function () {
            this.classList.remove('unread');
        });
    });

    // Fade-in cards on load
    document.querySelectorAll('.std-card, .stat-card, .service-card').forEach(function (card, i) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';
        setTimeout(function () {
            card.style.transition = 'opacity .3s ease, transform .3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 60 * i);
    });
});

// ===================================================
// Chatbot Functions
// ===================================================
const Chatbot = {
    conversationId: null,
    isTyping: false,

    init: function (convId) {
        this.conversationId = convId;
        this.scrollToBottom();

        const input = document.getElementById('chatInput');
        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    Chatbot.sendMessage();
                }
            });
        }
    },

    sendMessage: function () {
        const input = document.getElementById('chatInput');
        const message = input ? input.value.trim() : '';
        if (!message || this.isTyping) return;

        this.appendMessage('student', message);
        input.value = '';
        input.style.height = 'auto';

        this.showTyping();
        this.isTyping = true;

        const formData = new FormData();
        formData.append('message', message);
        if (this.conversationId) formData.append('conversation_id', this.conversationId);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

        fetch('/STD/backend/chatbot_process.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            this.hideTyping();
            this.isTyping = false;
            if (data.conversation_id) this.conversationId = data.conversation_id;
            this.appendMessage('bot', data.answer || 'عذراً، حدث خطأ في الاستجابة.');
            if (data.suggest_request) this.showRequestSuggestion();
        })
        .catch(() => {
            this.hideTyping();
            this.isTyping = false;
            this.appendMessage('bot', 'عذراً، حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
        });
    },

    appendMessage: function (type, text) {
        const container = document.getElementById('chatMessages');
        if (!container) return;

        const isUser = type === 'student';
        const time = new Date().toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });

        const msgHtml = `
            <div class="chat-message ${isUser ? 'user-msg' : 'bot-msg'}">
                ${!isUser ? '<div class="bot-avatar"><i class="bi bi-robot"></i></div>' : ''}
                <div>
                    <div class="chat-bubble">${this.formatText(text)}</div>
                    <div class="chat-time">${time}</div>
                </div>
                ${isUser ? '<img src="" alt="" class="chat-avatar" id="userChatAvatar" onerror="this.style.display=\'none\'">' : ''}
            </div>`;

        container.insertAdjacentHTML('beforeend', msgHtml);
        this.scrollToBottom();
    },

    formatText: function (text) {
        return text
            // 1. Escape HTML
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            // 2. Markdown links [text](url)
            .replace(/\[([^\]]+)\]\((\/[^)]+)\)/g, '<a href="$2" target="_blank" class="chat-link">$1 <i class="bi bi-download"></i></a>')
            // 3. Bold **text**
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            // 4. Bullet points • or -
            .replace(/(^|\n)[•\-] (.+)/g, '$1<span class="chat-bullet">•</span> $2')
            // 5. Line breaks
            .replace(/\n/g, '<br>');
    },

    showTyping: function () {
        const container = document.getElementById('chatMessages');
        if (!container) return;
        const html = `<div class="chat-message bot-msg" id="typingIndicator">
            <div class="bot-avatar"><i class="bi bi-robot"></i></div>
            <div class="chat-bubble">
                <div class="typing-indicator"><span></span><span></span><span></span></div>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
        this.scrollToBottom();
    },

    hideTyping: function () {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.remove();
    },

    showRequestSuggestion: function () {
        const container = document.getElementById('chatMessages');
        if (!container) return;
        const html = `<div class="chat-message bot-msg">
            <div class="bot-avatar"><i class="bi bi-robot"></i></div>
            <div>
                <div class="chat-bubble">
                    هل تريد تقديم طلب رسمي لمتابعة الأمر؟
                    <div class="mt-2">
                        <a href="/STD/student/new_request.php" class="btn btn-sm btn-light border me-1">
                            <i class="bi bi-plus-circle me-1"></i>تقديم طلب
                        </a>
                    </div>
                </div>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
        this.scrollToBottom();
    },

    scrollToBottom: function () {
        const container = document.getElementById('chatMessages');
        if (container) container.scrollTop = container.scrollHeight;
    },

    newConversation: function () {
        this.conversationId = null;
        const container = document.getElementById('chatMessages');
        if (container) container.innerHTML = `
            <div class="text-center py-4">
                <div class="bot-avatar mx-auto mb-2" style="width:56px;height:56px;font-size:1.5rem;">
                    <i class="bi bi-robot"></i>
                </div>
                <p class="text-muted small">مرحباً! أنا مساعدك الذكي في عمادة شؤون الطلاب.<br>كيف يمكنني مساعدتك اليوم؟</p>
            </div>`;
    }
};
