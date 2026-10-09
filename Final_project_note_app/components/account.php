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
        <!-- Right: profile fields -->
        <div class="account-main flex-grow-1 p-4 p-md-5 position-relative">
            <button class="edit-profile-btn" type="button" id="editProfileBtn">
                Edit profile
            </button>

            <h2 class="account-username mb-1" id="usernameDisplay">USERNAME</h2>
            <p class="account-subtitle mb-4">Change username</p>

            <form id="accountForm">
                <div class="row gy-4">
                    <div class="col-12 col-md-6">
                        <label class="account-field-label">
                            <i class="bi bi-envelope"></i> Email
                        </label>
                        <input type="email" class="account-field-input" value="" placeholder="you@example.com" readonly>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="account-field-label">
                            <i class="bi bi-calendar3"></i> Date of birth
                        </label>
                        <input type="text" class="account-field-input" value="" placeholder="dd/mm/yyyy" readonly>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="account-field-label">
                            <i class="bi bi-person-vcard"></i> Fullname
                        </label>
                        <input type="text" class="account-field-input" value="" placeholder="Your full name" readonly>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="account-field-label">
                            <i class="bi bi-gender-ambiguous"></i> Gender
                        </label>
                        <input type="text" class="account-field-input" value="" placeholder="Your gender" readonly>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>