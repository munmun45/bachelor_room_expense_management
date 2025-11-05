<!DOCTYPE html>
<html lang="en">

<head>
  <?php require("./config/meta.php") ?>
  <title>Groups Management - Bachelor Room Expense Management</title>
</head>

<body>
  <?php require("./config/header.php") ?>
  <?php require("./config/menu.php") ?>

  <?php
  require_once("./config/config.php");


  // Build dynamic conditions for filtering
  $conditions = [];
  if (!empty($_GET['creator'])) {
    $creator = mysqli_real_escape_string($conn, $_GET['creator']);
    $conditions[] = "u.full_name LIKE '%$creator%'";
  }
  $conditionSql = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

  // Fetch groups data with related information and dynamic filters
  $query = "SELECT rg.*, r.name as room_name, u.full_name as creator_name,
          (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = rg.id) as member_count,
          (SELECT u2.full_name FROM group_members gm2 JOIN users u2 ON u2.id = gm2.user_id WHERE gm2.group_id = rg.id AND gm2.is_leader = 1 LIMIT 1) as leader_name
          FROM room_groups rg
          LEFT JOIN rooms r ON rg.room_id = r.id
          LEFT JOIN users u ON rg.created_by = u.id" . $conditionSql . "
          ORDER BY rg.id DESC";
  $result = mysqli_query($conn, $query);
  // Build dynamic conditions for filtering
  $conditions = [];
  if (!empty($_GET['creator'])) {
    $creator = mysqli_real_escape_string($conn, $_GET['creator']);
    $conditions[] = "u.full_name LIKE '%$creator%'";
  }
  $conditionSql = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

  $query = "SELECT rg.*, r.name as room_name, u.full_name as creator_name,
          (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = rg.id) as member_count,
          (SELECT u2.full_name FROM group_members gm2 JOIN users u2 ON u2.id = gm2.user_id WHERE gm2.group_id = rg.id AND gm2.is_leader = 1 LIMIT 1) as leader_name
          FROM room_groups rg
          LEFT JOIN rooms r ON rg.room_id = r.id
          LEFT JOIN users u ON rg.created_by = u.id" . $conditionSql . "
          ORDER BY rg.id DESC";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Groups Management</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Group Management</li>
          <li class="breadcrumb-item active">Groups</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Groups List</h5>

              <!-- Filter Form -->
              <form method="GET" class="row g-3 mb-4">
                <div class="col-md-6">
                  <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Search by Creator" name="creator" value="<?php echo isset($_GET['creator']) ? htmlspecialchars($_GET['creator']) : ''; ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                    <a href="groups.php" class="btn btn-secondary ms-2"><i class="bi bi-x-circle"></i> Reset</a>
                  </div>
                </div>
              </form>
              <!-- End Filter Form -->
              <!-- Table with groups data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Group Name</th>
                    <th scope="col">Room</th>
                    <th scope="col">Members</th>
                    <th scope="col">Created By</th>
                    <th>Admin</th>
                    <th scope="col">Created At</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <th scope="row"><?php echo $row['id']; ?></th>
                      <td><?php echo $row['name']; ?></td>
                      <td><?php echo $row['room_name']; ?></td>
                      <td><?php echo $row['member_count']; ?></td>
                      <td><?php echo htmlspecialchars($row['creator_name']); ?></td>
                      <td><?php echo !empty($row['leader_name']) ? htmlspecialchars($row['leader_name']) : 'Not Assigned'; ?></td>
                      <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#groupModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                        <a href="group_members.php?group_id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                          <i class="bi bi-people"></i> Members
                        </a>
                      </td>
                    </tr>

                    <!-- Group Modal -->
                    <div class="modal fade" id="groupModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Group Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Group ID:</div>
                              <div class="col-md-8">#<?php echo $row['id']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Group Name:</div>
                              <div class="col-md-8"><?php echo $row['name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Room:</div>
                              <div class="col-md-8"><?php echo $row['room_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Description:</div>
                              <div class="col-md-8"><?php echo $row['description'] ? $row['description'] : 'No description available'; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Members:</div>
                              <div class="col-md-8"><?php echo $row['member_count']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Created By:</div>
                              <div class="col-md-8"><?php echo $row['creator_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Created At:</div>
                              <div class="col-md-8"><?php echo date('F j, Y, g:i a', strtotime($row['created_at'])); ?></div>
                            </div>
                            <?php if (!empty($row['updated_at'])) { ?>
                              <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Updated At:</div>
                                <div class="col-md-8"><?php echo date('F j, Y, g:i a', strtotime($row['updated_at'])); ?></div>
                              </div>
                            <?php } ?>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="group_members.php?group_id=<?php echo $row['id']; ?>" class="btn btn-primary">View Members</a>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- End Group Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with groups data -->
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