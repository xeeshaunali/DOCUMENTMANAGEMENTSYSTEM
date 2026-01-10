<?php
session_start();
if(isset($_SESSION['uid']))
{

}
else
{
    header('location:login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="./css/bootstrap.css">   
    <script src="datatable/jquery-3.7.0.js"></script>
    <style>
        body{
            margin: 0 auto;
            padding: 0px;
        }

    </style>

<style>
        /* Add a CSS rule to hide the button when printing */
        @media print {
            .print-button {
                display: none !important;
            }
        }
    </style>
    
    <title>ONE WINDOW || CTC</title>
</head>
<body>


<?php
// Your database connection code here
include "dbconn.php";

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$crimeno = isset($_GET['crimeno']) ? $_GET['crimeno'] :'';
$crimeyear = isset($_GET['crimeyear']) ? $_GET['crimeyear'] :'';
$ps = isset($_GET['ps']) ? $_GET['ps'] : 'all';
$caseno = isset($_GET['caseno']) ? $_GET['caseno'] :'';
$bundle = isset($_GET['bundle']) ? $_GET['bundle'] :'';
$year = isset($_GET['year']) ? $_GET['year'] :'';
$partyone = isset($_GET['partyone']) ? $_GET['partyone'] :'';
$partytwo = isset($_GET['partytwo']) ? $_GET['partytwo'] :'';
$court_name = isset($_GET['courtname']) ? $_GET['courtname'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$shelf = isset($_GET['shelf']) ? $_GET['shelf'] : 'all';


// Build the SQL query based on the selected filters
$sql = "SELECT * FROM ctccc WHERE status='LyingWithOneWindow'";

// Add conditions based on the provided filters
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND dateSubmission BETWEEN '$start_date' AND '$end_date'";    
}


// Filter by Police Statation
if ($ps !== 'all') {
    $sql .= " AND ps = '$ps'";
}


// Filter by Crime Number
if (!empty($crimeno)) {
    $sql .= " AND crimeno = '$crimeno'";
}

// Filter by Crime Year
if (!empty($crimeyear)) {
    $sql .= " AND crimeyear = '$crimeyear'";
}

// Filter by Case Number
if (!empty($caseno)) {
    $sql .= " AND caseno = '$caseno'";
}

// Filter by Case Year
if (!empty($year)) {
    $sql .= " AND year = '$year'";
}


// Filter by Party One
if (!empty($partyone)) {
    $sql .= " AND partyone like '%$partyone%'";
}

// Filter by Party Two
if (!empty($partytwo)) {
    $sql .= " AND partytwo like '%$partytwo%'";
}




// Filter by Court Name
if ($court_name !== 'all') {
    $sql .= " AND courtname = '$court_name'";
}

// Filter by Status
if ($status !== 'all') {
    $sql .= " AND status = '$status'";
}

// Filter by Shelf
if ($shelf !== 'all') {
    $sql .= " AND shelf = '$shelf'";
}


// Filter by bundle Number
if (!empty($bundle)) {
    $sql .= " AND bundle = '$bundle'";
}

$result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbs5uoF0Zl3+kv4o5C/gSA4gEhqU7TWq18LDnZwiabtGUpJ3pi5P3BYWBn2bvy5+J" crossorigin="anonymous">
    <title>Search Results</title>
    <style>
            tr th {
                border:2px solid black !important;
                text-align: center;             
            }
            td {
                border: 2px solid black;
                text-align: center;
                padding-top: 1px !important;
                padding-bottom:3px !important;


            }
    </style>
</head>
<body>

<div class="container-fluid mt-5">
    <h2 class="mb-4 text-center text-success fw-bolder">E-RECORD</h2>
    <?php
     if ($result && $result->num_rows > 0) {
        ?>
        <table class="table table-striped bordered">
            <thead>
            <tr>
                        <th>File No.</th>
                        <th>ID</th>                        
                        <th>Court<br>Name</th>
                        <th>Case<br>Categ</th>
                        <th>Case<br>No</th>
                        <th>Year</th>
                        <th>PartyOne</th>
                        <th>PartyTwo</th>
                        <th>CrimeNo</th>
                        <th>CrimeYear</th>
                        <th>P.S</th>
                        <!-- <th>Inst</th> -->
                        <th>Disposal</th>
                        <!-- <th>Submission In Record</th> -->
                        <th>Shelf</th>
                        <th>Row</th>
                        <th>Bundle</th>
                        
                        <th>Status</th>
                        <th>Remarks</th>
            </tr>
            </thead>
            <tbody>
            <?php
            while ($row = $result->fetch_assoc()) {
                ?>
                <tr>
                            <td><?php echo $row["file"]; ?></td>
                            <td><?php echo $row["id"]; ?></td>
                            <td><?php echo $row["courtname"]; ?></td>
                            <td><?php echo $row["casecateg"]; ?></td>
                            <td><?php echo $row["caseno"]; ?></td>
                            <td><?php echo $row["year"]; ?></td>
                            <td><?php echo $row["partyone"]; ?></td>
                            <td><?php echo $row["partytwo"]; ?></td>
                            <td><?php echo $row["crimeno"]; ?></td>
                            <td><?php echo $row["crimeyear"]; ?></td>
                            <td><?php echo $row["ps"]; ?></td>
                            <!-- <td><?php //echo $row["dateInst"]; ?></td> -->
                            <td><?php echo $row["dateDisp"]; ?></td>
                            <!-- <td><?php //echo $row["dateSubmission"]; ?></td> -->
                            <td><?php echo $row["shelf"]; ?></td>
                            <td><?php echo $row["row"]; ?></td>
                            <td><?php echo $row["bundle"]; ?></td>
                            
                            <td><?php echo $row["status"]; ?></td>
                            <td><?php echo $row["remarks"]; ?></td>
                </tr>
                <?php
            }
            mysqli_free_result($result);
            ?>
            </tbody>
        </table>
        <?php
    } else {
        echo "No records found.";
    }


    $con->close();

    ?>
</div>

    <div class="text-center mt-3 fw-bold">
            <div class="print-date">Printed on: <?php date_default_timezone_set('Asia/Karachi'); echo date('Y-m-d H:i:s'); 
            ?><br><br>
             <button class="btn btn-danger btn-lg shadow rounded print-button" onclick="printPage()">PRINT</button>
    </div>
                        
    </div>


<script>
    // Function to print the page
    function printPage() {
        window.print();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-eUHJ3A5Cf7pyRffLZ/Dagx3lfUq76EdW8eN7eUtjhKp5AR6Vr+Qe4izfcSeJfBA" crossorigin="anonymous"></script>
</body>
</html>
