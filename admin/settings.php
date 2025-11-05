<!DOCTYPE html>
<html lang="en">

<head>
  <?php require("./config/meta.php") ?>
</head>

<body>
  <?php require("./config/header.php") ?>
  <?php require("./config/menu.php") ?>

  <?php
  require_once("./config/config.php");


  // Fetch system settings
  $query = "SELECT * FROM settings";
  $result = mysqli_query($conn, $query);
  $settings = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
  }
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>System Settings</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">System</li>
          <li class="breadcrumb-item active">Settings</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">System Settings</h5>

              <!-- Settings Form -->
              <form class="row g-3">
                <div class="col-md-6">
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">General Settings</h5>

                      <div class="row mb-3">
                        <label for="siteName" class="col-sm-4 col-form-label">Site Name</label>
                        <div class="col-sm-8">
                          <input type="text" class="form-control" id="siteName" value="<?php echo isset($settings['site_name']) ? $settings['site_name'] : 'Bachelor Room Expense Management'; ?>">
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="siteEmail" class="col-sm-4 col-form-label">Site Email</label>
                        <div class="col-sm-8">
                          <input type="email" class="form-control" id="siteEmail" value="<?php echo isset($settings['site_email']) ? $settings['site_email'] : 'admin@example.com'; ?>">
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="sitePhone" class="col-sm-4 col-form-label">Contact Phone</label>
                        <div class="col-sm-8">
                          <input type="text" class="form-control" id="sitePhone" value="<?php echo isset($settings['site_phone']) ? $settings['site_phone'] : '+880 1234567890'; ?>">
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="siteAddress" class="col-sm-4 col-form-label">Address</label>
                        <div class="col-sm-8">
                          <textarea class="form-control" id="siteAddress" rows="3"><?php echo isset($settings['site_address']) ? $settings['site_address'] : 'Dhaka, Bangladesh'; ?></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">System Configuration</h5>

                      <div class="row mb-3">
                        <label for="kycRequired" class="col-sm-4 col-form-label">KYC Required</label>
                        <div class="col-sm-8">
                          <select class="form-select" id="kycRequired">
                            <option value="1" <?php echo (isset($settings['kyc_required']) && $settings['kyc_required'] == '1') ? 'selected' : ''; ?>>Yes</option>
                            <option value="0" <?php echo (isset($settings['kyc_required']) && $settings['kyc_required'] == '0') ? 'selected' : ''; ?>>No</option>
                          </select>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="userRegistration" class="col-sm-4 col-form-label">Allow Registration</label>
                        <div class="col-sm-8">
                          <select class="form-select" id="userRegistration">
                            <option value="1" <?php echo (isset($settings['allow_registration']) && $settings['allow_registration'] == '1') ? 'selected' : ''; ?>>Yes</option>
                            <option value="0" <?php echo (isset($settings['allow_registration']) && $settings['allow_registration'] == '0') ? 'selected' : ''; ?>>No</option>
                          </select>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="maintenanceMode" class="col-sm-4 col-form-label">Maintenance Mode</label>
                        <div class="col-sm-8">
                          <select class="form-select" id="maintenanceMode">
                            <option value="1" <?php echo (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'selected' : ''; ?>>Enabled</option>
                            <option value="0" <?php echo (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == '0') ? 'selected' : ''; ?>>Disabled</option>
                          </select>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="systemVersion" class="col-sm-4 col-form-label">System Version</label>
                        <div class="col-sm-8">
                          <input type="text" class="form-control" id="systemVersion" value="<?php echo isset($settings['system_version']) ? $settings['system_version'] : '1.0.0'; ?>" readonly>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-12">
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Notification Settings</h5>

                      <div class="row mb-3">
                        <label for="emailNotifications" class="col-sm-3 col-form-label">Email Notifications</label>
                        <div class="col-sm-9">
                          <select class="form-select" id="emailNotifications">
                            <option value="1" <?php echo (isset($settings['email_notifications']) && $settings['email_notifications'] == '1') ? 'selected' : ''; ?>>Enabled</option>
                            <option value="0" <?php echo (isset($settings['email_notifications']) && $settings['email_notifications'] == '0') ? 'selected' : ''; ?>>Disabled</option>
                          </select>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="smsNotifications" class="col-sm-3 col-form-label">SMS Notifications</label>
                        <div class="col-sm-9">
                          <select class="form-select" id="smsNotifications">
                            <option value="1" <?php echo (isset($settings['sms_notifications']) && $settings['sms_notifications'] == '1') ? 'selected' : ''; ?>>Enabled</option>
                            <option value="0" <?php echo (isset($settings['sms_notifications']) && $settings['sms_notifications'] == '0') ? 'selected' : ''; ?>>Disabled</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="text-center">
                  <button type="button" class="btn btn-primary">Save Changes</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
              </form>
              <!-- End Settings Form -->
            </div>
          </div>
        </div>
      </div>
    </section>
  </main><!-- End #main -->

  <?php require("./config/footer.php") ?>
</body>

</html>