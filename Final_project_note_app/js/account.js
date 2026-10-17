
    (function () {
        const editBtn = document.getElementById('editProfileBtn');
        if (!editBtn) return; 
        const inputs = document.querySelectorAll('.account-field-input');
        let editing = false;

        editBtn.addEventListener('click', function () {
            editing = !editing;
            inputs.forEach(function (input) {
                if (editing) {
                    input.removeAttribute('readonly');
                } else {
                    input.setAttribute('readonly', 'readonly');
                }
            });
            editBtn.textContent = editing ? 'Save profile' : 'Edit profile';
            if (editing) {
                inputs[0].focus();
            }
        });
    })();
