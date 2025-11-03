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


  // Get group ID from query string if available
  $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

  // Fetch expense messages with group information
  $query = "SELECT em.*, u.full_name, u.profile_img, g.name as group_name, r.name as room_name 
          FROM expense_messages em
          LEFT JOIN users u ON em.user_id = u.id
          LEFT JOIN groups g ON em.group_id = g.id
          LEFT JOIN rooms r ON g.room_id = r.id";

  // Filter by group if group_id is provided
  if ($group_id > 0) {
    $query .= " WHERE em.group_id = $group_id";
  }

  $query .= " ORDER BY em.created_at DESC";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Expense Messages</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Expense Management</li>
          <li class="breadcrumb-item active">Expense Messages</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">
                <?php if ($group_id > 0) {
                  $group_query = "SELECT g.name, r.name as room_name FROM groups g 
                                  LEFT JOIN rooms r ON g.room_id = r.id 
                                  WHERE g.id = $group_id";
                  $group_result = mysqli_query($conn, $group_query);
                  $group_data = mysqli_fetch_assoc($group_result);
                  echo "Expense Messages for " . $group_data['name'] . " (Room: " . $group_data['room_name'] . ")";
                } else {
                  echo "All Expense Messages";
                } ?>
              </h5>

              <?php if ($group_id == 0) { ?>
                <div class="mb-3">
                  <form action="" method="get" class="row g-3">
                    <div class="col-md-4">
                      <select name="group_id" class="form-select">
                        <option value="0">All Groups</option>
                        <?php
                        $groups_query = "SELECT g.id, g.name, r.name as room_name FROM groups g 
                                      LEFT JOIN rooms r ON g.room_id = r.id 
                                      ORDER BY g.name";
                        $groups_result = mysqli_query($conn, $groups_query);
                        while ($group = mysqli_fetch_assoc($groups_result)) {
                          echo "<option value='" . $group['id'] . "'>" . $group['name'] . " (Room: " . $group['room_name'] . ")</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                  </form>
                </div>
              <?php } ?>

              <!-- Table with expense messages data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Created By</th>
                    <th scope="col">Group</th>
                    <th scope="col">Room</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Date</th>
                    <th scope="col">Type</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <th scope="row"><?php echo $row['id']; ?></th>
                      <td>
                        <div class="d-flex align-items-center">
                          <?php if (!empty($row['profile_img'])) { ?>
                            <img src="../uploads/profile/<?php echo $row['profile_img']; ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                          <?php } else { ?>
                            <img src="../assets/img/profile-img.jpg" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                          <?php } ?>
                          <?php echo $row['full_name']; ?>
                        </div>
                      </td>
                      <td><?php echo $row['group_name']; ?></td>
                      <td><?php echo $row['room_name']; ?></td>
                      <td>৳<?php echo number_format($row['amount'], 2); ?></td>
                      <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                      <td>
                        <?php if ($row['type'] == 'food') { ?>
                          <span class="badge bg-success">Food</span>
                        <?php } else { ?>
                          <span class="badge bg-info">Special</span>
                        <?php } ?>
                      </td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                      </td>
                    </tr>

                    <!-- Message Modal -->
                    <div class="modal fade" id="messageModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Expense Message Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row mb-3">
                              <div class="col-md-4 text-center">
                                <?php if (!empty($row['profile_img'])) { ?>
                                  <img src="../uploads/profile/<?php echo $row['profile_img']; ?>" alt="Profile" class="rounded-circle img-fluid" style="max-width: 100px;">
                                <?php } else { ?>
                                  <img src="../assets/img/profile-img.jpg" alt="Profile" class="rounded-circle img-fluid" style="max-width: 100px;">
                                <?php } ?>
                              </div>
                              <div class="col-md-8">
                                <h5><?php echo $row['full_name']; ?></h5>
                                <p class="text-muted mb-1">Created on <?php echo date('F j, Y g:i A', strtotime($row['created_at'])); ?></p>
                              </div>
                            </div>

                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Group:</div>
                              <div class="col-md-8"><?php echo $row['group_name']; ?></div>
                            </div>
                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Room:</div>
                              <div class="col-md-8"><?php echo $row['room_name']; ?></div>
                            </div>
                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Amount:</div>
                              <div class="col-md-8">৳<?php echo number_format($row['amount'], 2); ?></div>
                            </div>
                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Type:</div>
                              <div class="col-md-8">
                                <?php if ($row['type'] == 'food') { ?>
                                  <span class="badge bg-success">Food</span>
                                <?php } else { ?>
                                  <span class="badge bg-info">Special</span>
                                <?php } ?>
                              </div>
                            </div>
                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Description:</div>
                              <div class="col-md-8"><?php echo $row['description']; ?></div>
                            </div>

                            <?php if (!empty($row['image'])) { ?>
                              <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Receipt:</div>
                                <div class="col-md-8">
                                  <a href="../uploads/expenses/<?php echo $row['image']; ?>" target="_blank">
                                    <img src="../uploads/expenses/<?php echo $row['image']; ?>" alt="Receipt" class="img-fluid" style="max-height: 200px;">
                                  </a>
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
                    <!-- End Message Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with expense messages data -->
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