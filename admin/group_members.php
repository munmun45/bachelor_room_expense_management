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

  // Fetch group members with group information
  $query = "SELECT gm.*, u.full_name, u.phone, u.email, u.profile_img, g.name as group_name, r.name as room_name 
          FROM group_members gm
          LEFT JOIN users u ON gm.user_id = u.id
          LEFT JOIN groups g ON gm.group_id = g.id
          LEFT JOIN rooms r ON g.room_id = r.id";

  // Filter by group if group_id is provided
  if ($group_id > 0) {
    $query .= " WHERE gm.group_id = $group_id";
  }

  $query .= " ORDER BY g.name, gm.joined_date DESC";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Group Members</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Group Management</li>
          <li class="breadcrumb-item active">Group Members</li>
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
                  echo "Members of " . $group_data['name'] . " (Room: " . $group_data['room_name'] . ")";
                } else {
                  echo "All Group Members";
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

              <!-- Table with group members data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Member</th>
                    <th scope="col">Group</th>
                    <th scope="col">Room</th>
                    <th scope="col">Role</th>
                    <th scope="col">Joined Date</th>
                    <th scope="col">Status</th>
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
                      <td>
                        <?php if ($row['is_admin'] == 1) { ?>
                          <span class="badge bg-primary">Admin</span>
                        <?php } else { ?>
                          <span class="badge bg-secondary">Member</span>
                        <?php } ?>
                      </td>
                      <td><?php echo date('M d, Y', strtotime($row['joined_date'])); ?></td>
                      <td>
                        <?php if ($row['status'] == 'active') { ?>
                          <span class="badge bg-success">Active</span>
                        <?php } else { ?>
                          <span class="badge bg-danger">Inactive</span>
                        <?php } ?>
                      </td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#memberModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                      </td>
                    </tr>

                    <!-- Member Modal -->
                    <div class="modal fade" id="memberModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Member Details</h5>
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
                                <p class="text-muted mb-1"><?php echo $row['phone']; ?></p>
                                <p class="text-muted"><?php echo $row['email'] ? $row['email'] : 'No email provided'; ?></p>
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
                              <div class="col-md-4 fw-bold">Role:</div>
                              <div class="col-md-8">
                                <?php if ($row['is_admin'] == 1) { ?>
                                  <span class="badge bg-primary">Admin</span>
                                <?php } else { ?>
                                  <span class="badge bg-secondary">Member</span>
                                <?php } ?>
                              </div>
                            </div>
                            <div class="row mb-2">
                              <div class="col-md-4 fw-bold">Joined Date:</div>
                              <div class="col-md-8"><?php echo date('F j, Y', strtotime($row['joined_date'])); ?></div>
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
                            <?php if (!empty($row['left_date'])) { ?>
                              <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Left Date:</div>
                                <div class="col-md-8"><?php echo date('F j, Y', strtotime($row['left_date'])); ?></div>
                              </div>
                            <?php } ?>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="users.php?id=<?php echo $row['user_id']; ?>" class="btn btn-primary">View User Profile</a>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- End Member Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with group members data -->
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