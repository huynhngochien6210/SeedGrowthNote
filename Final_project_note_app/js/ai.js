// Lấy các phần tử HTML
const aiToggle = document.getElementById("aiToggle");
const aiPanel = document.getElementById("aiPanel");
const aiClose = document.getElementById("aiClose");
const aiInput = document.getElementById("aiInput");
const aiSend = document.getElementById("aiSend");
const aiChat = document.getElementById("aiChat");

// Mở AI Panel
aiToggle.addEventListener("click", function () {
    aiPanel.style.display = "flex";
    aiToggle.style.display = "none";
});

// Đóng AI Panel
aiClose.addEventListener("click", function () {
    aiPanel.style.display = "none";
    aiToggle.style.display = "flex";
});

// Gửi tin nhắn
function sendMessage() {
    const message = aiInput.value.trim();
    // Nếu không nhập gì thì không gửi
    if (message === "") {
        return;
    }
    // Tạo tin nhắn của người dùng
    const userMessage = document.createElement("div");
    userMessage.classList.add("user-message");
    userMessage.textContent = message;
    // Thêm vào khung chat
    aiChat.appendChild(userMessage);
    // Xóa nội dung input
    aiInput.value = "";
    // Cuộn xuống tin nhắn mới nhất
    aiChat.scrollTop = aiChat.scrollHeight;
}

// Click nút Send
aiSend.addEventListener("click", sendMessage);
// Nhấn Enter để gửi
aiInput.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        sendMessage();
    }
});