<div class="modal" id="reviewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Review Application</h3>
            <span class="close" onclick="closeModal('reviewModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="approveContent" style="display: none;">
                <p>Are you sure you want to approve this application? The nurse will receive their login credentials via email.</p>
                <div class="form-group">
                    <label for="username">Generated Username</label>
                    <input type="text" id="username" class="form-control" value="nurse_1001" readonly>
                </div>
                <div class="form-group">
                    <label for="password">Generated Password</label>
                    <input type="text" id="password" class="form-control" value="hC@r3P@ss" readonly>
                </div>
            </div>
            <div id="rejectContent" style="display: none;">
                <p>Are you sure you want to reject this application? Please provide a reason that will be sent to the applicant.</p>
                <div class="form-group">
                    <label for="rejectionReason">Reason for Rejection</label>
                    <textarea id="rejectionReason" class="form-control" placeholder="Enter reason for rejection"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" onclick="closeModal('reviewModal')">Cancel</button>
            <button id="confirmAction" class="btn btn-primary">Confirm</button>
        </div>
    </div>
</div>