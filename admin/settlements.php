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

  
  // Fetch settlements data with related information
  $query = "SELECT s.*, rg.name as group_name, u.full_name as creator_name, r.name as room_name
          FROM settlements s
          LEFT JOIN room_groups rg ON s.group_id = rg.id
          LEFT JOIN users u ON s.created_by = u.id
          LEFT JOIN rooms r ON rg.room_id = r.id
          ORDER BY s.id DESC";
  $result = mysqli_query($conn, $query);
  ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Settlements Management</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Settlements</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Settlements List</h5>

              <!-- Table with settlements data -->
              <table class="table table-striped datatable">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Room</th>
                    <th scope="col">Group</th>
                    <th scope="col">Period</th>
                    <th scope="col">Created By</th>
                    <th scope="col">Created At</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                      <th scope="row"><?php echo $row['id']; ?></th>
                      <td><?php echo $row['room_name']; ?></td>
                      <td><?php echo $row['group_name']; ?></td>
                      <td><?php echo date('M d, Y', strtotime($row['from_date'])) . ' - ' . date('M d, Y', strtotime($row['to_date'])); ?></td>
                      <td><?php echo $row['creator_name']; ?></td>
                      <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                      <td>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#settlementModal<?php echo $row['id']; ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                      </td>
                    </tr>

                    <!-- Settlement Modal -->
                    <div class="modal fade" id="settlementModal<?php echo $row['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Settlement Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Settlement ID:</div>
                              <div class="col-md-8">#<?php echo $row['id']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Room:</div>
                              <div class="col-md-8"><?php echo $row['room_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Group:</div>
                              <div class="col-md-8"><?php echo $row['group_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Period:</div>
                              <div class="col-md-8"><?php echo date('F j, Y', strtotime($row['from_date'])) . ' - ' . date('F j, Y', strtotime($row['to_date'])); ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Created By:</div>
                              <div class="col-md-8"><?php echo $row['creator_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-4 fw-bold">Created At:</div>
                              <div class="col-md-8"><?php echo date('F j, Y, g:i a', strtotime($row['created_at'])); ?></div>
                            </div>

                            <hr>

                            <h5>Settlement Breakdown</h5>
                            <?php
                            $settlement_data = json_decode($row['settlement_data'], true);
                            if ($settlement_data) {
                            ?>
                              <div class="card">
                                <div class="card-body">
                                  <h6 class="card-subtitle mb-2 text-muted">Food Expenses</h6>
                                  <p>Total: ₹<?php echo number_format($settlement_data['total_food'], 2); ?></p>

                                  <?php if (!empty($settlement_data['food_expenses'])) { ?>
                                    <table class="table table-sm">
                                      <thead>
                                        <tr>
                                          <th>User</th>
                                          <th>Amount</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php foreach ($settlement_data['food_expenses'] as $expense) { ?>
                                          <tr>
                                            <td><?php echo $expense['full_name']; ?></td>
                                            <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                                          </tr>
                                        <?php } ?>
                                      </tbody>
                                    </table>
                                  <?php } else { ?>
                                    <p>No food expenses recorded.</p>
                                  <?php } ?>
                                </div>
                              </div>

                              <div class="card mt-3">
                                <div class="card-body">
                                  <h6 class="card-subtitle mb-2 text-muted">Special Items</h6>
                                  <p>Total: ₹<?php echo number_format($settlement_data['total_special'], 2); ?></p>

                                  <?php if (!empty($settlement_data['special_expenses'])) { ?>
                                    <table class="table table-sm">
                                      <thead>
                                        <tr>
                                          <th>User</th>
                                          <th>Amount</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php foreach ($settlement_data['special_expenses'] as $expense) { ?>
                                          <tr>
                                            <td><?php echo $expense['full_name']; ?></td>
                                            <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                                          </tr>
                                        <?php } ?>
                                      </tbody>
                                    </table>
                                  <?php } else { ?>
                                    <p>No special items recorded.</p>
                                  <?php } ?>
                                </div>
                              </div>

                              <div class="card mt-3">
                                <div class="card-body">
                                  <h6 class="card-subtitle mb-2 text-muted">Final Settlement</h6>

                                  <?php if (!empty($settlement_data['settlements'])) { ?>
                                    <table class="table table-sm">
                                      <thead>
                                        <tr>
                                          <th>User</th>
                                          <th>Type</th>
                                          <th>Spent</th>
                                          <th>Share</th>
                                          <th>Net</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php foreach ($settlement_data['settlements'] as $settlement) { ?>
                                          <tr>
                                            <td><?php echo $settlement['name']; ?></td>
                                            <td><?php echo ucfirst($settlement['type']); ?></td>
                                            <td>₹<?php echo number_format($settlement['spent'], 2); ?></td>
                                            <td>₹<?php echo number_format($settlement['share'], 2); ?></td>
                                            <td class="<?php echo $settlement['net'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                              <?php echo $settlement['net'] >= 0 ? '+' : ''; ?>₹<?php echo number_format($settlement['net'], 2); ?>
                                            </td>
                                          </tr>
                                        <?php } ?>
                                      </tbody>
                                    </table>
                                  <?php } else { ?>
                                    <p>No settlement data available.</p>
                                  <?php } ?>
                                </div>
                              </div>
                            <?php } else { ?>
                              <div class="alert alert-warning">
                                Settlement data is not available or could not be parsed.
                              </div>
                            <?php } ?>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- End Settlement Modal -->
                  <?php } ?>
                </tbody>
              </table>
              <!-- End Table with settlements data -->
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