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



  // Fetch users data with optional filters
  $conditions = [];
  if (!empty($_GET['name'])) {
    $name = mysqli_real_escape_string($conn, $_GET['name']);
    $conditions[] = "full_name LIKE '%$name%'";
  }
  if (!empty($_GET['phone'])) {
    $phone = mysqli_real_escape_string($conn, $_GET['phone']);
    $conditions[] = "phone LIKE '%$phone%'";
  }
  if (!empty($_GET['email'])) {
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    $conditions[] = "email LIKE '%$email%'";
  }
  if (!empty($_GET['room_number'])) {
    $room_number = mysqli_real_escape_string($conn, $_GET['room_number']);
    $conditions[] = "room_number LIKE '%$room_number%'";
  }
  $condition_sql = count($conditions) ? (' WHERE ' . implode(' AND ', $conditions)) : '';
  $query = "SELECT * FROM users" . $condition_sql . " ORDER BY id DESC";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Users Management</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Users</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Users List</h5>

              <!-- Search Form -->
              <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                  <input type="text" name="name" class="form-control" placeholder="Search by Name" value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                </div>
                <div class="col-md-2">
                  <input type="text" name="phone" class="form-control" placeholder="Search by Phone" value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
                </div>
                <div class="col-md-3">
                  <input type="text" name="email" class="form-control" placeholder="Search by Email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                </div>
                <div class="col-md-2">
                  <input type="text" name="room_number" class="form-control" placeholder="Search by Room" value="<?php echo isset($_GET['room_number']) ? htmlspecialchars($_GET['room_number']) : ''; ?>">
                </div>
                <div class="col-md-2 d-grid gap-2 d-md-block">
                  <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                  <a href="users.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                </div>
              </form>
              <!-- End Search Form -->

              <!-- Table with users data -->
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Profile</th>
                    <th scope="col">Full Name</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Email</th>
                    <th scope="col">Room Number</th>
                    <th scope="col">KYC Status</th>
                    <th scope="col">WhatsApp</th>
                    <th scope="col">Edit</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <th scope="row"><?php echo $row['id']; ?></th>
                      <td>
                        <img src="../uploads/profile/<?php echo $row['profile_image']; ?>" alt="Profile" class="rounded-circle" width="40">
                      </td>
                      <td><?php echo $row['full_name']; ?></td>
                      <td><?php echo $row['phone']; ?></td>
                      <td><?php echo $row['email'] ? $row['email'] : 'N/A'; ?></td>
                      <td><?php echo $row['room_number'] ? $row['room_number'] : 'N/A'; ?></td>
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
                        <?php $waNumber = preg_replace('/[^0-9]/', '', $row['phone']); ?>
                        <a href="https://wa.me/<?php echo $waNumber; ?>?text=<?php echo urlencode('Thank you for being a valued member of our community!'); ?>" target="_blank" class="btn btn-success btn-sm">
                          <i class="bi bi-whatsapp"></i> Send
                        </a>
                      </td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $row['id']; ?>"><i class="bi bi-pencil-square"></i> Edit</button>
                      </td>
                    </tr>

                    <!-- User Modal -->
                    <div class="modal fade" id="userModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">User Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row">
                              <div class="col-md-4 text-center mb-3">
                                <img src="../uploads/profile/<?php echo $row['profile_image']; ?>" alt="Profile" class="rounded-circle img-fluid" style="max-width: 150px;">
                                <h5 class="mt-2"><?php echo $row['full_name']; ?></h5>
                                <p class="text-muted"><?php echo $row['occupation'] ? $row['occupation'] : 'No occupation specified'; ?></p>
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
                                  <div class="col-md-4 fw-bold">Room Number:</div>
                                  <div class="col-md-8"><?php echo $row['room_number'] ? $row['room_number'] : 'N/A'; ?></div>
                                </div>
                                <div class="row mb-2">
                                  <div class="col-md-4 fw-bold">Joined Date:</div>
                                  <div class="col-md-8"><?php echo $row['joined_date'] ? date('F j, Y', strtotime($row['joined_date'])) : 'N/A'; ?></div>
                                </div>
                                <div class="row mb-2">
                                  <div class="col-md-4 fw-bold">Emergency Contact:</div>
                                  <div class="col-md-8"><?php echo $row['emergency_contact'] ? $row['emergency_contact'] : 'N/A'; ?></div>
                                </div>
                                <div class="row mb-2">
                                  <div class="col-md-4 fw-bold">KYC Status:</div>
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

                                <?php if ($row['kyc_status'] != 'pending') { ?>
                                  <h5 class="mt-3">KYC Documents</h5>
                                  <div class="row">
                                    <?php if ($row['id_proof']) { ?>
                                      <div class="col-md-4 mb-2">
                                        <a href="../uploads/kyc/<?php echo $row['id_proof']; ?>" target="_blank" class="d-block border p-2 text-center">
                                          <i class="bi bi-file-earmark-text fs-1"></i>
                                          <p class="mb-0">ID Proof</p>
                                        </a>
                                      </div>
                                    <?php } ?>

                                    <?php if ($row['address_proof']) { ?>
                                      <div class="col-md-4 mb-2">
                                        <a href="../uploads/kyc/<?php echo $row['address_proof']; ?>" target="_blank" class="d-block border p-2 text-center">
                                          <i class="bi bi-file-earmark-text fs-1"></i>
                                          <p class="mb-0">Address Proof</p>
                                        </a>
                                      </div>
                                    <?php } ?>

                                    <?php if ($row['selfie_verification']) { ?>
                                      <div class="col-md-4 mb-2">
                                        <a href="../uploads/kyc/<?php echo $row['selfie_verification']; ?>" target="_blank" class="d-block border p-2 text-center">
                                          <i class="bi bi-file-earmark-image fs-1"></i>
                                          <p class="mb-0">Selfie</p>
                                        </a>
                                      </div>
                                    <?php } ?>
                                  </div>
                                <?php } ?>
                              </div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- End User Modal -->

                    <!-- Edit User Modal -->
                    <div class="modal fade" id="editUserModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <form action="edit_user.php?id=<?php echo $row['id']; ?>" method="POST" enctype="multipart/form-data">
                            <div class="modal-header">
                              <h5 class="modal-title">Edit User</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <div class="row g-3">
                                <div class="col-md-6">
                                  <label class="form-label">Full Name</label>
                                  <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Phone</label>
                                  <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($row['phone']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Email</label>
                                  <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Room Number</label>
                                  <input type="text" name="room_number" class="form-control" value="<?php echo htmlspecialchars($row['room_number']); ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Joined Date</label>
                                  <input type="date" name="joined_date" class="form-control" value="<?php echo htmlspecialchars($row['joined_date'] ? date('Y-m-d', strtotime($row['joined_date'])) : ''); ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Occupation</label>
                                  <input type="text" name="occupation" class="form-control" value="<?php echo htmlspecialchars($row['occupation']); ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Emergency Contact</label>
                                  <input type="text" name="emergency_contact" class="form-control" value="<?php echo htmlspecialchars($row['emergency_contact']); ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">KYC Status</label>
                                  <select name="kyc_status" class="form-select">
                                    <option value="pending" <?php echo $row['kyc_status']=='pending'?'selected':''; ?>>Pending</option>
                                    <option value="verified" <?php echo $row['kyc_status']=='verified'?'selected':''; ?>>Verified</option>
                                    <option value="rejected" <?php echo $row['kyc_status']=='rejected'?'selected':''; ?>>Rejected</option>
                                  </select>
                                </div>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-success">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                    <!-- End Edit User Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with users data -->
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