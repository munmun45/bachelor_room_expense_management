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
  $query = "SELECT r.*, u.full_name as creator_name 
          FROM rooms r 
          LEFT JOIN users u ON r.created_by = u.id 
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

              <!-- Table with rooms data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Room Name</th>
                    <th scope="col">Address</th>
                    <th scope="col">Rental Date</th>
                    <th scope="col">Monthly Rent</th>
                    <th scope="col">Created By</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <th scope="row"><?php echo $row['id']; ?></th>
                      <td><?php echo $row['name']; ?></td>
                      <td><?php echo $row['address']; ?></td>
                      <td><?php echo $row['rental_date'] ? date('M d, Y', strtotime($row['rental_date'])) : 'N/A'; ?></td>
                      <td><?php echo $row['monthly_rent'] ? '₹' . number_format($row['monthly_rent'], 2) : 'N/A'; ?></td>
                      <td><?php echo $row['creator_name']; ?></td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                        <a href="room_members.php?room_id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                          <i class="bi bi-people"></i> Members
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