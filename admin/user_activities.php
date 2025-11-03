<!DOCTYPE html>
<html lang="en">

<head>
  <?php require("./config/meta.php") ?>
  <title>User Activities - Bachelor Room Expense Management</title>
</head>

<body>
  <?php require("./config/header.php") ?>
  <?php require("./config/menu.php") ?>

  <?php
  require_once("./config/config.php");

  // Fetch user activities
  $query = "SELECT a.*, u.full_name, u.profile_img 
          FROM user_activities a
          LEFT JOIN users u ON a.user_id = u.id
          ORDER BY a.created_at DESC";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>User Activities</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">User Management</li>
          <li class="breadcrumb-item active">User Activities</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Activity Log</h5>

              <!-- Table with activities data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">User</th>
                    <th scope="col">Activity Type</th>
                    <th scope="col">Description</th>
                    <th scope="col">Date & Time</th>
                    <th scope="col">IP Address</th>
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
                      <td>
                        <?php
                        $activity_class = '';
                        switch ($row['activity_type']) {
                          case 'login':
                            $activity_class = 'bg-primary';
                            break;
                          case 'logout':
                            $activity_class = 'bg-secondary';
                            break;
                          case 'create':
                            $activity_class = 'bg-success';
                            break;
                          case 'update':
                            $activity_class = 'bg-info';
                            break;
                          case 'delete':
                            $activity_class = 'bg-danger';
                            break;
                          default:
                            $activity_class = 'bg-dark';
                        }
                        ?>
                        <span class="badge <?php echo $activity_class; ?>"><?php echo ucfirst($row['activity_type']); ?></span>
                      </td>
                      <td><?php echo $row['description']; ?></td>
                      <td><?php echo date('M d, Y g:i A', strtotime($row['created_at'])); ?></td>
                      <td><?php echo $row['ip_address']; ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with activities data -->
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