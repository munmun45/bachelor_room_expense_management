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


  // Fetch absence requests with related information
  $query = "SELECT ar.*, u.full_name, u.profile_img, r.name as room_name 
          FROM absence_requests ar
          LEFT JOIN users u ON ar.user_id = u.id
          LEFT JOIN room_members rm ON u.id = rm.user_id
          LEFT JOIN rooms r ON rm.room_id = r.id
          ORDER BY ar.id DESC";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Absence Requests</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Room Management</li>
          <li class="breadcrumb-item active">Absence Requests</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Absence Requests List</h5>

              <!-- Table with absence requests data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">User</th>
                    <th scope="col">Room</th>
                    <th scope="col">From Date</th>
                    <th scope="col">To Date</th>
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
                            <img src="../uploads/profiles/<?php echo $row['profile_img']; ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                          <?php } else { ?>
                            <img src="../assets/img/profile-img.jpg" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                          <?php } ?>
                          <?php echo $row['full_name']; ?>
                        </div>
                      </td>
                      <td><?php echo $row['room_name']; ?></td>
                      <td><?php echo date('M d, Y', strtotime($row['from_date'])); ?></td>
                      <td><?php echo date('M d, Y', strtotime($row['to_date'])); ?></td>
                      <td>
                        <?php
                        $status_class = '';
                        switch ($row['status']) {
                          case 'pending':
                            $status_class = 'bg-warning';
                            break;
                          case 'approved':
                            $status_class = 'bg-success';
                            break;
                          case 'rejected':
                            $status_class = 'bg-danger';
                            break;
                          default:
                            $status_class = 'bg-secondary';
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span>
                      </td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#absenceModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                      </td>
                    </tr>

                    <!-- Absence Request Modal -->
                    <div class="modal fade" id="absenceModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Absence Request Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Request ID:</div>
                              <div class="col-md-8">#<?php echo $row['id']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">User:</div>
                              <div class="col-md-8"><?php echo $row['full_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Room:</div>
                              <div class="col-md-8"><?php echo $row['room_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Period:</div>
                              <div class="col-md-8"><?php echo date('F j, Y', strtotime($row['from_date'])) . ' - ' . date('F j, Y', strtotime($row['to_date'])); ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Status:</div>
                              <div class="col-md-8">
                                <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span>
                              </div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Reason:</div>
                              <div class="col-md-8"><?php echo $row['reason']; ?></div>
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

                            <?php if ($row['status'] == 'pending') { ?>
                              <hr>
                              <div class="row">
                                <div class="col-md-6">
                                  <form action="process_absence.php" method="post">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success w-100">Approve</button>
                                  </form>
                                </div>
                                <div class="col-md-6">
                                  <form action="process_absence.php" method="post">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger w-100">Reject</button>
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
                    <!-- End Absence Request Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with absence requests data -->
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