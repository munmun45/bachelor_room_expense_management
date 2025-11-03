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

  // Fetch admin users
  $query = "SELECT * FROM admin_users ORDER BY id";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Admin Users</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">System</li>
          <li class="breadcrumb-item active">Admin Users</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Admin Users</h5>

              <!-- Table with admin users data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Username</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Last Login</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <th scope="row"><?php echo $row['id']; ?></th>
                      <td><?php echo $row['username']; ?></td>
                      <td><?php echo $row['name']; ?></td>
                      <td><?php echo $row['email']; ?></td>
                      <td>
                        <?php if ($row['role'] == 'super_admin') { ?>
                          <span class="badge bg-danger">Super Admin</span>
                        <?php } else { ?>
                          <span class="badge bg-primary">Admin</span>
                        <?php } ?>
                      </td>
                      <td><?php echo !empty($row['last_login']) ? date('M d, Y H:i', strtotime($row['last_login'])) : 'Never'; ?></td>
                      <td>
                        <?php if ($row['status'] == 'active') { ?>
                          <span class="badge bg-success">Active</span>
                        <?php } else { ?>
                          <span class="badge bg-danger">Inactive</span>
                        <?php } ?>
                      </td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#adminModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                      </td>
                    </tr>

                    <!-- Admin Modal -->
                    <div class="modal fade" id="adminModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Admin User Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row mb-3">
                              <div class="col-md-4 text-center">
                                <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                              </div>
                              <div class="col-md-8">
                                <h5><?php echo $row['name']; ?></h5>
                                <p class="text-muted mb-1"><?php echo $row['username']; ?></p>
                                <p class="text-muted"><?php echo $row['email']; ?></p>
                              </div>
                            </div>

                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Role:</div>
                              <div class="col-md-8">
                                <?php if ($row['role'] == 'super_admin') { ?>
                                  <span class="badge bg-danger">Super Admin</span>
                                <?php } else { ?>
                                  <span class="badge bg-primary">Admin</span>
                                <?php } ?>
                              </div>
                            </div>
                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Status:</div>
                              <div class="col-md-8">
                                <?php if ($row['status'] == 'active') { ?>
                                  <span class="badge bg-success">Active</span>
                                <?php } else { ?>
                                  <span class="badge bg-danger">Inactive</span>
                                <?php } ?>
                              </div>
                            </div>
                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Created:</div>
                              <div class="col-md-8"><?php echo date('F j, Y', strtotime($row['created_at'])); ?></div>
                            </div>
                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Last Login:</div>
                              <div class="col-md-8"><?php echo !empty($row['last_login']) ? date('F j, Y H:i:s', strtotime($row['last_login'])) : 'Never'; ?></div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- End Admin Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with admin users data -->
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