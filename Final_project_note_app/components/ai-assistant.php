    <!-- Button mở AI -->
    <button
        class="ai-toggle"
        id="aiToggle"
        type="button"
    >
        ✦
    </button>

    <!-- AI Panel -->
    <aside
        class="ai-panel"
        id="aiPanel"
    >
 
        <!-- Header -->
            <div class="ai-header">
                <div class="ai-title">
                    <div class="ai-logo">
                        <i class="bi bi-tree-fill"></i>
                    </div>
                    <span>Trợ lý nông trại</span>
                </div>
            <button
                class="ai-close"
                id="aiClose"
                type="button"
            >
                ×
            </button>
        </div>

        <!-- Chat -->
        <div class="ai-chat">

            <!-- Tin nhắn AI mẫu -->
            <div class="ai-message">
                Xin chào! Tôi là Trợ lý nông trại.
                Tôi có thể hỗ trợ bạn về cây trồng và ghi chú.
            </div>

            <!-- Tin nhắn người dùng -->
            <div class="user-message">
                Xin chào!
            </div>
        </div>

        <!-- Input -->
        <div class="ai-input-area">
            <input
                type="text"
                class="ai-input"
                id="aiInput"
                placeholder="Nhập câu hỏi..."
            >
            <button
                type="button"
                class="ai-send"
                id="aiSend"
            >
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
    </aside>