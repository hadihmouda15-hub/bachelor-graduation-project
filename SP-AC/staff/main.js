
// Application tabs
document.querySelectorAll("[data-app-tab]").forEach((tab) => {
  tab.addEventListener("click", function () {
    document
      .querySelectorAll("[data-app-tab]")
      .forEach((t) => t.classList.remove("active"));
    this.classList.add("active");
    // In a real app, you would filter the table here
  });
});

// Report tabs
document.querySelectorAll("[data-report-tab]").forEach((tab) => {
  tab.addEventListener("click", function () {
    document
      .querySelectorAll("[data-report-tab]")
      .forEach((t) => t.classList.remove("active"));
    this.classList.add("active");
    // In a real app, you would filter the table here
  });
});

// Notification recipient selection
document
  .getElementById("notificationRecipient")
  .addEventListener("change", function () {
    const specificNurseGroup = document.getElementById("specificNurseGroup");
    const nurseGroupGroup = document.getElementById("nurseGroupGroup");

    specificNurseGroup.style.display = "none";
    nurseGroupGroup.style.display = "none";

    if (this.value === "specific") {
      specificNurseGroup.style.display = "block";
    } else if (this.value === "group") {
      nurseGroupGroup.style.display = "block";
    }
  });

// Form submission
document
  .getElementById("notificationForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();
    alert("Notification sent successfully!");
    this.reset();
    document.getElementById("specificNurseGroup").style.display = "none";
    document.getElementById("nurseGroupGroup").style.display = "none";
  });

// Modal functions
function openReviewModal(id, action) {
  const modal = document.getElementById("reviewModal");
  const modalTitle = document.getElementById("modalTitle");
  const approveContent = document.getElementById("approveContent");
  const rejectContent = document.getElementById("rejectContent");
  const confirmBtn = document.getElementById("confirmAction");

  if (action === "approve") {
    modalTitle.textContent = "Approve Application";
    approveContent.style.display = "block";
    rejectContent.style.display = "none";
    confirmBtn.textContent = "Approve";
    confirmBtn.className = "btn btn-success";
    confirmBtn.onclick = function () {
      alert(`Application ${id} approved! Credentials sent to nurse's email.`);
      closeModal("reviewModal");
      // In a real app, you would update the UI and send data to the server
    };
  } else {
    modalTitle.textContent = "Reject Application";
    approveContent.style.display = "none";
    rejectContent.style.display = "block";
    confirmBtn.textContent = "Reject";
    confirmBtn.className = "btn btn-danger";
    confirmBtn.onclick = function () {
      const reason = document.getElementById("rejectionReason").value;
      if (!reason) {
        alert("Please provide a reason for rejection");
        return;
      }
      alert(`Application ${id} rejected. Reason sent to applicant.`);
      closeModal("reviewModal");
      // In a real app, you would update the UI and send data to the server
    };
  }

  modal.style.display = "flex";
}

function viewApplication(id) {
  // In a real app, you would fetch the application details based on ID
  const modal = document.getElementById("viewApplicationModal");
  modal.style.display = "flex";
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
}

// Close modal when clicking outside of it
window.addEventListener("click", function (event) {
  if (event.target.className === "modal") {
    event.target.style.display = "none";
  }
});
