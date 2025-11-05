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

  // Fetch users with KYC data
  $conditions = [];
  if (!empty($_GET['name'])) {
    $name = mysqli_real_escape_string($conn, $_GET['name']);
    $conditions[] = "full_name LIKE '%$name%'";
  }
  if (!empty($_GET['phone'])) {
    $phone = mysqli_real_escape_string($conn, $_GET['phone']);
    $conditions[] = "phone LIKE '%$phone%'";
  }
  if (!empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $conditions[] = "kyc_status = '$status'";
  }
  $conditionSql = !empty($conditions) ? ' AND ' . implode(' AND ', $conditions) : '';

  $query = "SELECT * FROM users WHERE kyc_status IS NOT NULL $conditionSql ORDER BY 
          CASE 
            WHEN kyc_status = 'pending' THEN 1
            WHEN kyc_status = 'verified' THEN 2
            WHEN kyc_status = 'rejected' THEN 3
            ELSE 4
          END";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>KYC Verification</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">User Management</li>
          <li class="breadcrumb-item active">KYC Verification</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">KYC Verification Requests</h5>
              <!-- Search Form -->
              <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                  <input type="text" name="name" class="form-control" placeholder="Search by Name" value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                </div>
                <div class="col-md-3">
                  <input type="text" name="phone" class="form-control" placeholder="Search by Phone" value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
                </div>
                <div class="col-md-3">
                  <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="verified" <?php echo (isset($_GET['status']) && $_GET['status'] == 'verified') ? 'selected' : ''; ?>>Verified</option>
                    <option value="rejected" <?php echo (isset($_GET['status']) && $_GET['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                  </select>
                </div>
                <div class="col-md-3 d-grid gap-2 d-md-block">
                  <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                  <a href="kyc_verification.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                </div>
              </form>
              <!-- End Search Form -->

              <!-- Table with KYC data -->
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">User</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Submission Date</th>
                    <th scope="col">Status</th>
                    <th scope="col">WhatsApp</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($result)) {
                    // Skip users without KYC documents
                    if (empty($row['id_proof']) && empty($row['address_proof']) && empty($row['selfie_verification'])) {
                      continue;
                    }
                  ?>
                    <tr>
                      <th scope="row"><?php echo $row['id']; ?></th>
                      <td>
                        <div class="d-flex align-items-center">
                          <?php if (!empty($row['profile_image'])) { ?>
                            <img src="../uploads/profile/<?php echo $row['profile_image']; ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                          <?php } else { ?>
                            <img src="../assets/img/profile-img.jpg" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                          <?php } ?>
                          <?php echo $row['full_name']; ?>
                        </div>
                      </td>
                      <td><?php echo $row['phone']; ?></td>
                      <td><?php echo date('M d, Y', strtotime($row['kyc_submitted_at'] ?? $row['created_at'])); ?></td>
                      <td>
                        <?php if ($row['kyc_status'] == 'verified') { ?>
                          <span class="badge bg-success">Verified</span>
                        <?php } elseif ($row['kyc_status'] == 'rejected') { ?>
                          <span class="badge bg-danger">Rejected</span>
                        <?php } else { ?>
                          <span class="badge bg-warning">Pending</span>
                        <?php } ?>
                      </td>
                      <td>
                        <?php
                        $waNumber = preg_replace('/[^0-9]/', '', $row['phone']);
                        if ($row['kyc_status'] == 'verified') {
                          $waMessage = 'Congratulations! Your KYC has been approved.';
                        } elseif ($row['kyc_status'] == 'rejected') {
                          $waMessage = 'We are sorry, your KYC was rejected. Please contact support.';
                        } else {
                          $waMessage = 'Your KYC verification is pending. We will notify you once it is completed.';
                        }
                        ?>
                        <a href="https://wa.me/<?php echo $waNumber; ?>?text=<?php echo urlencode($waMessage); ?>" target="_blank" class="btn btn-success btn-sm"><i class="bi bi-whatsapp"></i> Send</a>
                      </td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kycModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> Review
                        </button>
                      </td>
                    </tr>

                    <!-- KYC Modal -->
                    <div class="modal fade" id="kycModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">KYC Verification - <?php echo $row['full_name']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row mb-3">
                              <div class="col-md-4 text-center">
                                <?php if (!empty($row['profile_image'])) { ?>
                                  <img src="../uploads/profile/<?php echo $row['profile_image']; ?>" alt="Profile" class="rounded-circle img-fluid" style="max-width: 150px;">
                                <?php } else { ?>
                                  <img src="../assets/img/profile-img.jpg" alt="Profile" class="rounded-circle img-fluid" style="max-width: 150px;">
                                <?php } ?>
                                <h5 class="mt-2"><?php echo $row['full_name']; ?></h5>
                              </div>
                              <div class="col-md-8">
                                <h5>Personal Information</h5>
                                <div class="row mb-2">
                                  <div class="col-md-4 fw-bold">Phone:</div>
                                  <div class="col-md-8"><?php echo $row['phone']; ?></div>
                                </div>
                                <div class="row mb-2">
                                  <div class="col-md-4 fw-bold">Email:</div>
                                  <div class="col-md-8"><?php echo $row['email'] ? $row['email'] : 'N/A'; ?></div>
                                </div>
                                <div class="row mb-2">
                                  <div class="col-md-4 fw-bold">Status:</div>
                                  <div class="col-md-8">
                                    <?php if ($row['kyc_status'] == 'verified') { ?>
                                      <span class="badge bg-success">Verified</span>
                                    <?php } elseif ($row['kyc_status'] == 'rejected') { ?>
                                      <span class="badge bg-danger">Rejected</span>
                                    <?php } else { ?>
                                      <span class="badge bg-warning">Pending</span>
                                    <?php } ?>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <h5 class="mt-4">KYC Documents</h5>
                            <div class="row">
                              <?php if (!empty($row['id_proof'])) { ?>
                                <div class="col-md-4 mb-3">
                                  <div class="card h-100">
                                    <div class="card-header">ID Proof</div>
                                    <div class="card-body text-center">
                                      <a href="../uploads/kyc/<?php echo $row['id_proof']; ?>" target="_blank">
                                        <img src="../uploads/kyc/<?php echo $row['id_proof']; ?>" alt="ID Proof" class="img-fluid mb-2" style="max-height: 150px;">
                                        <p><i class="bi bi-eye"></i> View Full Size</p>
                                      </a>
                                    </div>
                                  </div>
                                </div>
                              <?php } ?>

                              <?php if (!empty($row['address_proof'])) { ?>
                                <div class="col-md-4 mb-3">
                                  <div class="card h-100">
                                    <div class="card-header">Address Proof</div>
                                    <div class="card-body text-center">
                                      <a href="../uploads/kyc/<?php echo $row['address_proof']; ?>" target="_blank">
                                        <img src="../uploads/kyc/<?php echo $row['address_proof']; ?>" alt="Address Proof" class="img-fluid mb-2" style="max-height: 150px;">
                                        <p><i class="bi bi-eye"></i> View Full Size</p>
                                      </a>
                                    </div>
                                  </div>
                                </div>
                              <?php } ?>

                              <?php if (!empty($row['selfie_verification'])) { ?>
                                <div class="col-md-4 mb-3">
                                  <div class="card h-100">
                                    <div class="card-header">Selfie Verification</div>
                                    <div class="card-body text-center">
                                      <a href="../uploads/kyc/<?php echo $row['selfie_verification']; ?>" target="_blank">
                                        <img src="../uploads/kyc/<?php echo $row['selfie_verification']; ?>" alt="Selfie Verification" class="img-fluid mb-2" style="max-height: 150px;">
                                        <p><i class="bi bi-eye"></i> View Full Size</p>
                                      </a>
                                    </div>
                                  </div>
                                </div>
                              <?php } ?>
                            </div>

                            <?php if ($row['kyc_status'] == 'pending') { ?>
                              <div class="row mt-4">
                                <div class="col-12">
                                  <form action="process/verify_kyc.php" method="post">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <div class="mb-3">
                                      <label for="remarks<?php echo $row['id']; ?>" class="form-label">Remarks</label>
                                      <textarea class="form-control" id="remarks<?php echo $row['id']; ?>" name="remarks" rows="3"></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                      <button type="submit" name="action" value="reject" class="btn btn-danger me-2">Reject</button>
                                      <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            <?php } ?>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- End KYC Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with KYC data -->
            </div>
          </div>
        </div>
      </div>
    </section>
  </main><!-- End #main -->

  <?php require("./config/footer.php") ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize datatable
      new simpleDatatables.DataTable(".datatable");
    });
  </script>
</body>

</html>