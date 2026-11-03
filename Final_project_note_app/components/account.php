<!-- Account panel -->
<div class="app-panel account-page-wrap" data-panel-name="account">
    <div class="account-card d-flex flex-column flex-md-row position-relative">

        <!-- Back -->
        <button
            type="button"
            class="panel-back-btn nav-panel-btn"
            data-panel="notes"
            aria-label="Back"
        >
            <i class="bi bi-arrow-left"></i>
        </button>

        <!-- Left -->
        <div class="account-side d-flex flex-column align-items-center text-center px-4 py-5">

            
        <div class="avatar-circle mb-2">
            <i class="bi bi-person-fill avatar-placeholder"></i>

            <img
                class="account-avatar-img"
                alt="Profile avatar"
                hidden
            >

            <button
                class="avatar-edit-btn"
                type="button"
                aria-label="Change avatar"
            >
                <i class="bi bi-camera-fill"></i>
            </button>
        </div>


            <button
                type="button"
                class="change-avatar-text mb-4"
                id="changeAvatarText"
            >
                Change avatar
            </button>

            <input
                type="file"
                id="avatarFileInput"
                accept="image/jpeg,image/png,image/webp"
                hidden
            >

            <p
                id="avatarMessage"
                class="small text-white mt-2"
                role="status"
                aria-live="polite"
            ></p>

            <ul class="account-menu list-unstyled w-100">
                <li class="account-menu-item active">
                    <span class="account-menu-dot"></span>
                    <a href="#">Account info</a>
                </li>

                <li class="account-menu-item">
                    <span class="account-menu-dot"></span>
                    <a href="#" class="nav-panel-btn" data-panel="password">
                        Password
                    </a>
                </li>

                <li class="account-menu-item logout">
                    <span class="account-menu-dot"></span>
                    <a href="#">Log out</a>
                </li>
            </ul>
        </div>

        <!-- Right -->
        <div class="account-main flex-grow-1 p-4 p-md-5 position-relative">

            <button
                class="edit-profile-btn"
                type="button"
                id="editProfileBtn"
            >
                Edit profile
            </button>

            <h2 class="account-username mb-1" id="usernameDisplay">
                USERNAME
            </h2>

            <p class="account-subtitle mb-4">
                Change username
            </p>

            <form id="accountForm">
                <div class="row gy-4">

                    <!-- Email -->
                    <div class="col-12 col-md-6">
                        <label class="account-field-label" for="profileEmail">
                            <i class="bi bi-envelope"></i>
                            Email
                        </label>

                        <input
                            type="email"
                            id="profileEmail"
                            class="account-field-input"
                            placeholder="you@example.com"
                            readonly
                        >
                    </div>

                    <!-- Date of birth -->
                    <div class="col-12 col-md-6">
                        <label class="account-field-label" for="profileDob">
                            <i class="bi bi-calendar3"></i>
                            Date of birth
                        </label>

                        <input
                            type="date"
                            id="profileDob"
                            class="account-field-input"
                            readonly
                        >
                    </div>

                    <!-- Fullname -->
                    <div class="col-12 col-md-6">
                        <label class="account-field-label" for="profileName">
                            <i class="bi bi-person-vcard"></i>
                            Fullname
                        </label>

                        <input
                            type="text"
                            id="profileName"
                            class="account-field-input"
                            placeholder="Your full name"
                            maxlength="255"
                            readonly
                        >
                    </div>

                    <!-- Gender -->
                    <div class="col-12 col-md-6">
                        <label class="account-field-label" for="profileGender">
                            <i class="bi bi-gender-ambiguous"></i>
                            Gender
                        </label>

                        <select
                            id="profileGender"
                            class="account-field-input"
                            disabled
                        >
                            <option value="">Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                </div>

                <p
                    id="profileMessage"
                    class="mt-4"
                    role="status"
                    aria-live="polite"
                ></p>
            </form>

        </div>
    </div>
</div>
