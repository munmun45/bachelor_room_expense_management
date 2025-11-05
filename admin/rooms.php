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

  // Fetch rooms data with creator information
  // Build dynamic conditions based on filters
  $conditions = [];
  if (!empty($_GET['room_name'])) {
    $roomName = mysqli_real_escape_string($conn, $_GET['room_name']);
    $conditions[] = "r.name LIKE '%$roomName%'";
  }
  if (!empty($_GET['address'])) {
    $address = mysqli_real_escape_string($conn, $_GET['address']);
    $conditions[] = "r.address LIKE '%$address%'";
  }
  if (!empty($_GET['admin_name'])) {
    $adminName = mysqli_real_escape_string($conn, $_GET['admin_name']);
    // filter rooms where admin's full name matches search text
    $conditions[] = "EXISTS (SELECT 1 FROM room_members rm3 JOIN users u3 ON u3.id = rm3.user_id WHERE rm3.room_id = r.id AND rm3.is_admin = 1 AND u3.full_name LIKE '%$adminName%')";
  }
  $conditionSql = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

  $query = "SELECT r.*, 
            u.full_name as creator_name,
            (SELECT COUNT(*) FROM room_members rm WHERE rm.room_id = r.id) AS member_count,
            (SELECT u2.full_name FROM room_members rm2 JOIN users u2 ON u2.id = rm2.user_id WHERE rm2.room_id = r.id AND rm2.is_admin = 1 LIMIT 1) AS admin_name
          FROM rooms r 
          LEFT JOIN users u ON r.created_by = u.id" . $conditionSql . "
          ORDER BY r.id DESC";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Rooms Management</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Rooms</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Rooms List</h5>

              <!-- Filter Form -->
              <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                  <input type="text" name="room_name" class="form-control" placeholder="Search by Room Name" value="<?php echo isset($_GET['room_name']) ? htmlspecialchars($_GET['room_name']) : ''; ?>">
                </div>
                <div class="col-md-3">
                  <input type="text" name="address" class="form-control" placeholder="Search by Address" value="<?php echo isset($_GET['address']) ? htmlspecialchars($_GET['address']) : ''; ?>">
                </div>
                <div class="col-md-3">
                  <input type="text" name="admin_name" class="form-control" placeholder="Search by Admin Name" value="<?php echo isset($_GET['admin_name']) ? htmlspecialchars($_GET['admin_name']) : ''; ?>">
                </div>
                <div class="col-md-3 d-grid gap-2 d-md-block">
                  <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                  <a href="rooms.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                </div>
              </form>
              <!-- End Filter Form -->

              <!-- Table with rooms data -->
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Room Name</th>
                    <th scope="col">Address</th>
                    <th scope="col">Rental Date</th>
                    <th scope="col">Monthly Rent</th>
                    <th>Members</th>
                    <th>Admin</th>
                    <th scope="col">Created By</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <th scope="row"><?php echo $row['id']; ?></th>
                      <td><?php echo $row['name']; ?></td>
                      <td><?php
                          $addr = $row['address'];
                          echo (strlen($addr) > 10) ? htmlspecialchars(substr($addr, 0, 10)) . '...' : htmlspecialchars($addr);
                          ?></td>
                      <td><?php echo $row['rental_date'] ? date('M d, Y', strtotime($row['rental_date'])) : 'N/A'; ?></td>
                      <td><?php echo $row['monthly_rent'] ? '₹' . number_format($row['monthly_rent'], 2) : 'N/A'; ?></td>
                      <td><?php echo $row['member_count']; ?></td>
                      <td><?php echo !empty($row['admin_name']) ? htmlspecialchars($row['admin_name']) : 'Not Assigned'; ?></td>
                      <td><?php echo htmlspecialchars($row['creator_name']); ?></td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $row['id']; ?>" title="View Details">
                          <i class="bi bi-eye"></i>
                        </button>
                        <a href="room_members.php?room_id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" title="View Members">
                          <i class="bi bi-people"></i>
                        </a>
                      </td>
                    </tr>

                    <!-- Room Modal -->
                    <div class="modal fade" id="roomModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Room Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Room Name:</div>
                              <div class="col-md-8"><?php echo $row['name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Description:</div>
                              <div class="col-md-8"><?php echo $row['description'] ? $row['description'] : 'No description'; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Address:</div>
                              <div class="col-md-8"><?php echo $row['address']; ?></div>
                            </div>
                            <?php if ($row['latitude'] && $row['longitude']) { ?>
                              <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Location:</div>
                                <div class="col-md-8">
                                  <a href="https://maps.google.com/?q=<?php echo $row['latitude']; ?>,<?php echo $row['longitude']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-geo-alt"></i> View on Map
                                  </a>
                                </div>
                              </div>
                            <?php } ?>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Rental Date:</div>
                              <div class="col-md-8"><?php echo $row['rental_date'] ? date('F j, Y', strtotime($row['rental_date'])) : 'N/A'; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Monthly Rent:</div>
                              <div class="col-md-8"><?php echo $row['monthly_rent'] ? '₹' . number_format($row['monthly_rent'], 2) : 'N/A'; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Advance Payment:</div>
                              <div class="col-md-8"><?php echo $row['advance_payment'] ? '₹' . number_format($row['advance_payment'], 2) : 'N/A'; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Created By:</div>
                              <div class="col-md-8"><?php echo $row['creator_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Created At:</div>
                              <div class="col-md-8"><?php echo date('F j, Y, g:i a', strtotime($row['created_at'])); ?></div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="room_members.php?room_id=<?php echo $row['id']; ?>" class="btn btn-primary">View Members</a>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- End Room Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with rooms data -->
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