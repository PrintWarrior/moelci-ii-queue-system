<!-- Edit User modal: populated by openEditModal() in scripts.php. -->
  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" id="editUserForm">
          <div class="modal-body">
            <input type="hidden" name="user_id" id="editUserId">

            <div class="form-grid mb-3">
              <div>
                <label class="form-label">Username</label>
                <input type="text" name="username" id="editUsername" class="form-control" required>
              </div>
              <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" id="editEmail" class="form-control">
              </div>
              <div>
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" id="editFirstName" class="form-control" required>
              </div>
              <div>
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" id="editLastName" class="form-control" required>
              </div>
              <div>
                <label class="form-label">Role</label>
                <select name="role_id" id="editRoleId" class="form-control" required onchange="toggleEditServiceAssignment()">
                  <option value="">Select Role</option>
                  <?php foreach ($edit_roles as $role): ?>
                    <option value="<?php echo $role['role_id']; ?>"><?php echo $role['role_name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="form-label">Status</label>
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1" checked>
                  <label class="form-check-label" for="editIsActive">Active</label>
                </div>
              </div>
            </div>

            <div id="editServiceAssignment" style="display: none;">
              <h6>Assign Services (for Tellers)</h6>
              <div class="service-checkboxes">
                <?php foreach ($services as $service): ?>
                  <div class="service-checkbox-item form-check">
                    <input class="form-check-input service-checkbox" type="checkbox" name="services[]" value="<?php echo $service['service_id']; ?>" id="edit_service_<?php echo $service['service_id']; ?>">
                    <label class="form-check-label" for="edit_service_<?php echo $service['service_id']; ?>">
                      <?php echo htmlspecialchars($service['service_name']); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
          </div>
        </form>
      </div>
    </div>
  </div>
