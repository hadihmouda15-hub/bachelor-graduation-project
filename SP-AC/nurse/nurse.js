// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    // Earnings Chart
    const earningsCtx = document.getElementById('earningsChart').getContext('2d');
    const earningsChart = new Chart(earningsCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Earnings',
                data: [0, 0, 0, 0, 0, 1250, 1850, 2200, 1800, 1600, 1250, 0],
                backgroundColor: '#4e73df',
                borderColor: '#4e73df',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.raw;
                        }
                    }
                }
            }
        }
    });

    // Availability toggle functionality
    document.querySelectorAll('.form-check-input').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const day = this.id.replace('Toggle', '');
            const slots = document.getElementById(day + 'Slots');
            const addBtn = document.querySelector(`.add-slot-btn[data-day="${day}"]`);

            if (this.checked) {
                slots.style.display = 'block';
                addBtn.style.display = 'inline-block';
            } else {
                slots.style.display = 'none';
                addBtn.style.display = 'none';
            }
        });
    });

    // Add time slot button
    document.querySelectorAll('.add-slot-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const day = this.getAttribute('data-day');
            const slotsContainer = document.getElementById(day + 'Slots');

            const slotDiv = document.createElement('div');
            slotDiv.className = 'mb-2 d-flex align-items-center';
            slotDiv.innerHTML = `
                <input type="time" class="form-control form-control-sm me-1" value="09:00">
                <span class="small me-1">to</span>
                <input type="time" class="form-control form-control-sm me-2" value="17:00">
                <button type="button" class="btn btn-sm btn-outline-danger remove-slot-btn">&times;</button>
            `;

            slotsContainer.appendChild(slotDiv);

            // Add remove functionality
            slotDiv.querySelector('.remove-slot-btn').addEventListener('click', function() {
                slotDiv.remove();
            });
        });
    });

    // Exception type toggle
    document.getElementById('exceptionType').addEventListener('change', function() {
        const hoursDiv = document.getElementById('exceptionHours');
        if (this.value === 'custom') {
            hoursDiv.style.display = 'flex';
        } else {
            hoursDiv.style.display = 'none';
        }
    });

    // Add exception button
    document.getElementById('addExceptionBtn').addEventListener('click', function() {
        const date = document.getElementById('exceptionDate').value;
        const type = document.getElementById('exceptionType').value;
        const start = document.getElementById('exceptionStart').value;
        const end = document.getElementById('exceptionEnd').value;

        if (!date) {
            alert('Please select a date');
            return;
        }

        const exceptionsList = document.getElementById('exceptionsList').querySelector('.list-group');
        const listItem = document.createElement('div');
        listItem.className = 'list-group-item d-flex justify-content-between align-items-center';

        let text;
        if (type === 'unavailable') {
            text = `<strong>${formatDate(date)}</strong> - Unavailable All Day`;
        } else {
            if (!start || !end) {
                alert('Please enter both start and end times');
                return;
            }
            text = `<strong>${formatDate(date)}</strong> - Available from ${formatTime(start)} to ${formatTime(end)}`;
        }

        listItem.innerHTML = `
            <div>${text}</div>
            <button type="button" class="btn btn-sm btn-outline-danger">Remove</button>
        `;

        exceptionsList.appendChild(listItem);

        // Add remove functionality
        listItem.querySelector('button').addEventListener('click', function() {
            listItem.remove();
        });

        // Clear form
        document.getElementById('exceptionDate').value = '';
        document.getElementById('exceptionType').value = 'unavailable';
        document.getElementById('exceptionHours').style.display = 'none';
        document.getElementById('exceptionStart').value = '';
        document.getElementById('exceptionEnd').value = '';
    });

    function formatDate(dateString) {
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    function formatTime(timeString) {
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Image Upload Functionality
    document.getElementById('profileImageUpload').addEventListener('change', function(e) {
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
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

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

            // Transfer current values to edit fields
            document.getElementById('editFirstName').value = document.getElementById('viewFirstName').textContent;
            document.getElementById('editLastName').value = document.getElementById('viewLastName').textContent;
            document.getElementById('editEmail').value = document.getElementById('viewEmail').textContent;
            document.getElementById('editPhone').value = document.getElementById('viewPhone').textContent;
            document.getElementById('editAddress').value = document.getElementById('viewAddress').textContent.replace(/<br>/g, '\n');
            document.getElementById('editExperience').value = document.getElementById('viewExperience').textContent;
            document.getElementById('editRate').value = document.getElementById('viewRate').textContent.replace('$', '');
            document.getElementById('editBio').value = document.getElementById('viewBio').textContent;
        } else {
            viewMode.style.display = 'block';
            editMode.style.display = 'none';
            editProfileBtn.textContent = 'Edit Profile';
        }
    }

    editProfileBtn.addEventListener('click', toggleEditMode);
    cancelEditBtn.addEventListener('click', toggleEditMode);

    // Form Submission
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();

        if (isEditMode) {
            // Update view mode with edited values
            document.getElementById('viewFirstName').textContent = document.getElementById('editFirstName').value;
            document.getElementById('viewLastName').textContent = document.getElementById('editLastName').value;
            document.getElementById('viewEmail').textContent = document.getElementById('editEmail').value;
            document.getElementById('viewPhone').textContent = document.getElementById('editPhone').value;
            document.getElementById('viewAddress').innerHTML = document.getElementById('editAddress').value.replace(/\n/g, '<br>');
            document.getElementById('viewExperience').textContent = document.getElementById('editExperience').value;
            document.getElementById('viewRate').textContent = '$' + document.getElementById('editRate').value;
            document.getElementById('viewBio').innerHTML = document.getElementById('editBio').value.replace(/\n/g, '<br>');

            // Handle specialties (simplified for demo)
            const selectedOptions = Array.from(document.getElementById('editSpecialties').selectedOptions);
            const specialties = selectedOptions.map(option => option.value).join(', ');
            document.getElementById('viewSpecialties').textContent = specialties;

            toggleEditMode();
        }
    });

    function populateRequestDetails(data) {
        // Basic info
        document.getElementById('requestIdBadge').textContent = data.id;
        document.getElementById('detailServiceType').textContent = data.serviceType;
        document.getElementById('detailRequestDate').textContent = new Date().toLocaleDateString();
        document.getElementById('detailScheduledTime').textContent = data.dateTime;
        document.getElementById('detailStatus').innerHTML = `<span class="badge bg-warning">Pending</span>`;
        document.getElementById('detailUrgency').textContent = data.urgency.includes("Urgent") ? "Urgent" : "Standard";
        document.getElementById('detailDuration').textContent = data.duration;
        document.getElementById('detailPayment').textContent = data.payment;

        // Patient info
        document.getElementById('detailPatientName').textContent = data.patient;
        document.getElementById('detailPatientCondition').textContent = data.patientCondition;
        document.getElementById('detailPatientPhoto').src = data.patientPhoto;
        document.getElementById('detailPatientGender').textContent = data.patientGender;
        document.getElementById('detailPatientAge').textContent = data.patientAge;
        document.getElementById('detailPatientPhone').textContent = data.patientPhone;

        // Location and instructions
        document.getElementById('detailServiceAddress').textContent = data.address;
        document.getElementById('detailMedicalHistory').textContent = data.medicalHistory;
        document.getElementById('detailAllergies').textContent = data.allergies;
        document.getElementById('detailMedications').textContent = data.medications;
        document.getElementById('detailSpecialInstructions').textContent = data.instructions;

        // Connect buttons
        document.getElementById('contactPatientBtn').onclick = function() {
            // This would open the messages tab with this patient pre-selected
            bootstrap.Modal.getInstance(document.getElementById('requestDetailsModal')).hide();
            document.querySelector('[href="#messages"]').click();
        };

        // document.getElementById('acceptRequestBtn').onclick = function() {
        //     alert('Request accepted successfully!');
        //     bootstrap.Modal.getInstance(document.getElementById('requestDetailsModal')).hide();
        //     // In a real app, you would update the UI and send data to the server
        // };
    }

    // Complete service button (in accepted requests table)
    document.querySelectorAll('.btn-info').forEach(btn => {
        if (btn.textContent.trim() === 'Complete') {
            btn.addEventListener('click', function() {
                new bootstrap.Modal(document.getElementById('completeServiceModal')).show();
            });
        }
    });

    // Cancel request button (in accepted requests table)
    document.querySelectorAll('.btn-danger').forEach(btn => {
        if (btn.textContent.trim() === 'Cancel') {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const requestId = row.cells[0].textContent;

                if (confirm(`Cancel service request ${requestId}?`)) {
                    // In a real app, you would send this to your server
                    alert('Request cancelled successfully!');
                    row.remove();
                }
            });
        }
    });

    // Complete service form submission
    document.getElementById('completeServiceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Service marked as completed!');
        bootstrap.Modal.getInstance(document.getElementById('completeServiceModal')).hide();
        // In a real app, you would update the UI and send data to the server
    });

    // for the log out
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default link behavior
        var logoutModal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
        logoutModal.show();
    });
});

// Public Request Acceptance
// document.querySelectorAll('.accept-public-request').forEach(btn => {
//     btn.addEventListener('click', function() {
//         const requestItem = this.closest('.list-group-item');
//         const requestTitle = requestItem.querySelector('h6').textContent;

//         if (confirm(`Accept this public request: "${requestTitle}"?`)) {
//             // In a real app, you would send this to your server
//             alert('Request accepted! You will be connected with the patient shortly.');
//             requestItem.remove();

//             // Update badge count
//             const badge = document.querySelector('[href="#public-requests"] .badge');
//             if (badge) {
//                 const currentCount = parseInt(badge.textContent);
//                 badge.textContent = currentCount > 1 ? currentCount - 1 : '';
//                 if (badge.textContent === '') badge.remove();
//             }
//         }
//     });
// });

// private request code
// Private Request Handling
// document.querySelectorAll('.accept-private-request').forEach(btn => {
//     btn.addEventListener('click', function() {
//         const requestItem = this.closest('.list-group-item');
//         const patientName = requestItem.querySelector('h6').textContent.split(' - ')[0];

//         if (confirm(`Accept private request from ${patientName}?`)) {
//             // In real app, send to server
//             const badge = requestItem.querySelector('.badge');
//             badge.className = 'badge bg-success';
//             badge.textContent = 'Accepted';

//             // Remove action buttons
//             this.closest('div').remove();

//             // Update sidebar badge count
//             updatePrivateRequestsBadge(-1);
//         }
//     });
// });

document.querySelectorAll('.decline-private-request').forEach(btn => {
    btn.addEventListener('click', function() {
        const requestItem = this.closest('.list-group-item');
        const patientName = requestItem.querySelector('h6').textContent.split(' - ')[0];

        if (confirm(`Decline private request from ${patientName}?`)) {
            // In real app, send to server
            requestItem.remove();

            // Update sidebar badge count
            updatePrivateRequestsBadge(-1);
        }
    });
});

function updatePrivateRequestsBadge(change) {
    const badge = document.querySelector('[href="#private-requests"] .badge');
    if (badge) {
        const currentCount = parseInt(badge.textContent) || 0;
        const newCount = currentCount + change;

        if (newCount > 0) {
            badge.textContent = newCount;
        } else {
            badge.remove();
        }
    }
}

// requests status code 
// Status Filter Functionality
// document.querySelectorAll('[data-status]').forEach(filter => {
//     filter.addEventListener('click', function(e) {
//         e.preventDefault();
//         const status = this.getAttribute('data-status');

//         // Update active state
//         document.querySelectorAll('[data-status]').forEach(item => {
//             item.classList.remove('active');
//         });
//         this.classList.add('active');

//         // Filter table rows
//         const rows = document.querySelectorAll('#statusTable tbody tr');
//         rows.forEach(row => {
//             if (status === 'all') {
//                 row.style.display = '';
//             } else {
//                 row.style.display = row.getAttribute('data-status') === status ? '' : 'none';
//             }
//         });
//     });
// });

// Status Badge Colors
const statusColors = {
    'pending': 'bg-warning',
    'patient-pending': 'bg-info',
    'accepted': 'bg-primary',
    'completed': 'bg-success',
    'cancelled': 'bg-secondary',
    'expired': 'bg-dark'
};




document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      const openModal = document.querySelector('.modal.show');
      if (openModal) {
        const modalInstance = bootstrap.Modal.getInstance(openModal);
        if (modalInstance) {
          modalInstance.hide();
        }
      }
    }
  });
  