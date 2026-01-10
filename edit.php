<?php
session_start();
include('dbconn.php'); // Include your database connection file

// Check if the user is logged in and is an admin
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); // Redirect to login if not an admin
    exit;
}

$successMessage = '';
$errorMessage = '';
$record = null;

// Handle search and update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        // Search for the record by ID
        $searchId = $_POST['id'];
        $query = "SELECT * FROM ctccc WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param('i', $searchId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();
        } else {
            $errorMessage = 'No record found with this ID.';
        }
    } elseif (isset($_POST['update'])) {
        // Update the record
        $updateId = $_POST['id'];
        $underSection = $_POST['underSection'];
        $courtname = $_POST['courtname'];
        $casecateg = $_POST['casecateg'];
        $caseno = $_POST['caseno'];
        $year = $_POST['year'];
        $partyone = $_POST['partyone'];
        $partytwo = $_POST['partytwo'];
        $crimeno = $_POST['crimeno'];
        $crimeyear = $_POST['crimeyear'];
        $s_rbf = $_POST['s_rbf'];
        $dateInst = $_POST['dateInst'];
        $dateSubmission = $_POST['dateSubmission'];
        $dateDisp = $_POST['dateDisp'];
        $status = $_POST['status'];
        $cost = $_POST['cost'];
        $remarks = $_POST['remarks'];
        $ps = $_POST['ps'];
        $row = $_POST['row'];
        $shelf = $_POST['shelf'];
        $bundle = $_POST['bundle'];
        $file = $_POST['file'];

        $query = "UPDATE ctccc SET 
            underSection = ?, courtname = ?, casecateg = ?, caseno = ?, year = ?, partyone = ?, partytwo = ?, crimeno = ?, crimeyear = ?, 
            s_rbf = ?, dateInst = ?, dateSubmission = ?, dateDisp = ?, status = ?, cost = ?, remarks = ?, ps = ?, row = ?, shelf = ?, 
            bundle = ?, file = ? 
            WHERE id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param(
            'sssisisssssssiisssiiii',
            $underSection, $courtname, $casecateg, $caseno, $year, $partyone, $partytwo, $crimeno, $crimeyear, $s_rbf, 
            $dateInst, $dateSubmission, $dateDisp, $status, $cost, $remarks, $ps, $row, $shelf, $bundle, $file, $updateId
        );

        if ($stmt->execute()) {
            $successMessage = 'Record updated successfully.';
            // Refresh the record after update
            $record = [
                'underSection' => $underSection, 'courtname' => $courtname, 'casecateg' => $casecateg, 'caseno' => $caseno,
                'year' => $year, 'partyone' => $partyone, 'partytwo' => $partytwo, 'crimeno' => $crimeno, 'crimeyear' => $crimeyear,
                's_rbf' => $s_rbf, 'dateInst' => $dateInst, 'dateSubmission' => $dateSubmission, 'dateDisp' => $dateDisp,
                'status' => $status, 'cost' => $cost, 'remarks' => $remarks, 'ps' => $ps, 'row' => $row, 'shelf' => $shelf,
                'bundle' => $bundle, 'file' => $file, 'id' => $updateId
            ];
        } else {
            $errorMessage = 'Error updating record. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record</title>
    <!-- Include Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 3rem;
        }
        .form-container {
            background-color: #f8f9fa;
            padding: 3rem;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10 form-container">
            <h2 class="text-center mb-4">Edit Record by ID</h2>

            <!-- Display success or error message -->
            <?php if ($successMessage): ?>
                <div class="alert alert-success"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <!-- Search Form -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="id" class="form-label">Enter Record ID</label>
                    <input type="number" class="form-control" name="id" id="id" required>
                </div>
                <div class="text-center">
                    <button type="submit" name="search" class="btn btn-primary">Search Record</button>
                </div>
            </form>

            <?php if ($record): ?>
                <!-- Edit Form for displaying all fields -->
                <form method="POST" action="" class="mt-4">
                    <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                    <!-- Input fields for each attribute -->
                    <?php
                    foreach ($record as $field => $value) {
                        if ($field !== 'id') { // Skip the ID field as it is primary key and should not be editable
                            echo "<div class='mb-3'>
                                    <label for='{$field}' class='form-label'>".ucfirst($field)."</label>
                                    <input type='text' class='form-control' name='{$field}' id='{$field}' value='".htmlspecialchars($value)."' required>
                                  </div>";
                        }
                    }
                    ?>
                    <div class="text-center">
                        <button type="submit" name="update" class="btn btn-success">Update Record</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"></script>

</body>
</html>
