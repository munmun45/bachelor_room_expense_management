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



  // Fetch users data
  $query = "SELECT * FROM users ORDER BY id DESC";
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

              <!-- Table with users data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Profile</th>
                    <th scope="col">Full Name</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Email</th>
                    <th scope="col">Room Number</th>
                    <th scope="col">KYC Status</th>
                    <th scope="col">Actions</th>
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
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                        <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                          <i class="bi bi-pencil"></i> Edit
                        </a>
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
                            <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Edit User</a>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- End User Modal -->
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