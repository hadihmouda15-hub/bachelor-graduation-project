document.addEventListener('DOMContentLoaded', function () {
    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Rating star interaction
    document.querySelectorAll('.rating-star').forEach(star => {
        star.addEventListener('click', function () {
            const rating = parseInt(this.getAttribute('data-rating'));
            document.getElementById('ratingValue').value = rating;

            document.querySelectorAll('.rating-star').forEach(s => {
                s.classList.toggle('text-warning', parseInt(s.getAttribute('data-rating')) <= rating);
                s.classList.toggle('text-secondary', parseInt(s.getAttribute('data-rating')) > rating);
            });
        });

        star.addEventListener('mouseover', function () {
            const rating = parseInt(this.getAttribute('data-rating'));
            document.querySelectorAll('.rating-star').forEach(s => {
                s.classList.toggle('text-warning', parseInt(s.getAttribute('data-rating')) <= rating);
                s.classList.toggle('text-secondary', parseInt(s.getAttribute('data-rating')) > rating);
            });
        });

        star.addEventListener('mouseout', function () {
            const currentRating = parseInt(document.getElementById('ratingValue').value) || 0;
            document.querySelectorAll('.rating-star').forEach(s => {
                s.classList.toggle('text-warning', parseInt(s.getAttribute('data-rating')) <= currentRating);
                s.classList.toggle('text-secondary', parseInt(s.getAttribute('data-rating')) > currentRating);
            });
        });
    });

    // Reset rating form when modal is closed
    const ratingModal = document.getElementById('ratingModal');
    if (ratingModal) {
        ratingModal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('ratingForm').reset();
            document.getElementById('ratingValue').value = 0;
            document.querySelectorAll('.rating-star').forEach(s => {
                s.classList.remove('text-warning');
                s.classList.add('text-secondary');
            });
        });
    }

    // Edit Profile Toggle Functionality
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const viewMode = document.getElementById('viewMode');
    const editMode = document.getElementById('editMode');

    let isEditMode = false;

    function toggleEditMode() {
        isEditMode = !isEditMode;

        if (isEditMode) {
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
            editProfileBtn.textContent = 'View Profile';

            document.getElementById('editFirstName').value = document.getElementById('viewFirstName').textContent;
            document.getElementById('editLastName').value = document.getElementById('viewLastName').textContent;
            document.getElementById('editEmail').value = document.getElementById('viewEmail').textContent;
            document.getElementById('editPhone').value = document.getElementById('viewPhone').textContent;
            document.getElementById('editAddress').value = document.getElementById('viewAddress').textContent.replace(/<br>/g, '\n');
            document.getElementById('editMedicalHistory').value = document.getElementById('viewMedicalHistory').textContent.replace(/<br>/g, '\n');
        } else {
            viewMode.style.display = 'block';
            editMode.style.display = 'none';
            editProfileBtn.textContent = 'Edit Profile';
        }
    }

    if (editProfileBtn && cancelEditBtn) {
        editProfileBtn.addEventListener('click', toggleEditMode);
        cancelEditBtn.addEventListener('click', toggleEditMode);
    }

    // Multi-step Form
    const formSteps = document.querySelectorAll('.step');
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    let currentStep = 0;

    function updateStep() {
        formSteps.forEach((step, index) => {
            step.style.display = index === currentStep ? 'block' : 'none';
        });
    }

    function validateStep1() {
        const serviceId = document.getElementById('service_id');
        const date = document.getElementById('date');
        const time = document.getElementById('time');
        const numberOfNurses = document.getElementById('number_of_nurses');

        let isValid = true;

        if (!serviceId || serviceId.value === '') {
            serviceId.classList.add('is-invalid');
            isValid = false;
        } else {
            serviceId.classList.remove('is-invalid');
        }

        if (!date || !date.value) {
            date.classList.add('is-invalid');
            isValid = false;
        } else {
            date.classList.remove('is-invalid');
        }

        if (!time || !time.value) {
            time.classList.add('is-invalid');
            isValid = false;
        } else {
            time.classList.remove('is-invalid');
        }

        if (!numberOfNurses || !numberOfNurses.value || numberOfNurses.value < 1) {
            numberOfNurses.classList.add('is-invalid');
            isValid = false;
        } else {
            numberOfNurses.classList.remove('is-invalid');
        }

        return isValid;
    }

    function validateStep2() {
        const careNeededCheckboxes = document.querySelectorAll('input[name="care_needed[]"]');
        const careNeededFeedback = document.getElementById('careNeededFeedback');
        let isValid = false;

        careNeededCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                isValid = true;
            }
        });

        if (!isValid) {
            careNeededFeedback.style.display = 'block';
        } else {
            careNeededFeedback.style.display = 'none';
        }

        const genderSelect = document.getElementById('gender');
        if (!genderSelect.value) {
            genderSelect.value = 'No Preference';
        }

        const ageTypeSelect = document.getElementById('age_type');
        if (!ageTypeSelect.value) {
            ageTypeSelect.value = 'No Preference';
        }

        return isValid;
    }

    updateStep();

    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentStep === 0) {
                if (validateStep1()) {
                    currentStep++;
                    updateStep();
                }
            } else if (currentStep === 1) {
                if (validateStep2()) {
                    currentStep++;
                    updateStep();
                }
            } else if (currentStep < formSteps.length - 1) {
                currentStep++;
                updateStep();
            }
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentStep > 0) {
                currentStep--;
                updateStep();
            }
        });
    });

    const durationSelect = document.getElementById('durationSelect');
    const customDuration = document.getElementById('customDuration');
    if (durationSelect && customDuration) {
        durationSelect.addEventListener('change', function () {
            if (this.value === 'Custom') {
                customDuration.style.display = 'block';
                customDuration.required = true;
            } else {
                customDuration.style.display = 'none';
                customDuration.required = false;
                customDuration.value = '';
                customDuration.classList.remove('is-invalid');
            }
        });
    }

    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        reportForm.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Issue report submitted successfully! Our team will review it shortly.');
        });
    }

    const profileImageUpload = document.getElementById('profileImageUpload');
    const submitImageBtn = document.getElementById('submitImageBtn');
    if (profileImageUpload && submitImageBtn) {
        profileImageUpload.addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('profileImagePreview');
            const errorElement = document.getElementById('uploadError');

            errorElement.style.display = 'none';

            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                errorElement.textContent = 'File is too large (max 5MB)';
                errorElement.style.display = 'block';
                return;
            }

            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                errorElement.textContent = 'Only JPG/PNG files allowed';
                errorElement.style.display = 'block';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                submitImageBtn.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }
});