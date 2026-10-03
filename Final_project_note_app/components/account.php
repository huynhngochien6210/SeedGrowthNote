<!-- Account panel: 1 trong các "feature panel" được đổi qua lại bằng JS -->
<div class="app-panel account-page-wrap" data-panel-name="account">
    <div class="account-card d-flex flex-column flex-md-row position-relative">

        <!-- Nút quay lại panel Notes (mặc định) -->
        <button type="button" class="panel-back-btn nav-panel-btn" data-panel="notes" aria-label="Back">
            <i class="bi bi-arrow-left"></i>
        </button>

        <!-- Left: avatar + menu -->
        <div class="account-side d-flex flex-column align-items-center text-center px-4 py-5">
            <div class="avatar-circle mb-2">
                <i class="bi bi-person-fill"></i>
                <button class="avatar-edit-btn" type="button" aria-label="Change avatar">
                    <i class="bi bi-camera-fill"></i>
                </button>
            </div>
            <div class="small text-white-50 mb-4">Change avatar</div>

            <ul class="account-menu list-unstyled w-100">
                <li class="account-menu-item active">
                    <span class="account-menu-dot"></span>
                    <a href="#">Account info</a>
                </li>
                <li class="account-menu-item">
                    <span class="account-menu-dot"></span>
                    <a href="#" class="nav-panel-btn" data-panel="password">Password</a>
                </li>
                <li class="account-menu-item logout">
                    <span class="account-menu-dot"></span>
                    <a href="#">Log out</a>
                </li>
            </ul>
        </div>
    </div>
</div>