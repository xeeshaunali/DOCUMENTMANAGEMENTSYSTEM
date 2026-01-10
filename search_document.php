<?php
include 'dbconn.php';
$case_id = intval($_GET['case_id'] ?? 0);
if ($case_id <= 0) {
    echo "<p class='text-danger'>Invalid Case ID.</p>";
    exit;
}
$q = $con->prepare("SELECT * FROM case_documents WHERE case_id=? ORDER BY uploaded_at DESC");
$q->bind_param("i", $case_id);
$q->execute();
$res = $q->get_result();
if ($res->num_rows === 0) {
    echo "<p class='text-muted'>No documents found for Case ID $case_id.</p>";
    exit;
}
echo "<table class='table table-sm table-striped'><thead><tr><th>#</th><th>Filename</th><th>By</th><th>Date</th><th>Action</th></tr></thead><tbody>";
while($row = $res->fetch_assoc()){
    $path = htmlspecialchars($row['file_path']);
    $id = (int)$row['id'];
    echo "<tr>
            <td>{$id}</td>
            <td>".htmlspecialchars($row['file_name'])."</td>
            <td>".htmlspecialchars($row['uploaded_by'])."</td>
            <td>".htmlspecialchars($row['uploaded_at'])."</td>
            <td>
                <button class='btn btn-sm btn-outline-primary' onclick=\"previewFile('$path')\">View</button>
                <button class='btn btn-sm btn-outline-danger' onclick='deleteDoc($id)'>Delete</button>
            </td>
          </tr>";
}
echo "</tbody></table>";
$q->close();
?>
