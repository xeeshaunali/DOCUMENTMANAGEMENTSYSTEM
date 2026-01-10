<?php
include 'dbconn.php';
$id=(int)($_GET['id'] ?? 0);
if(!$id) exit('Invalid ID.');

$q=$con->prepare("SELECT file_path FROM case_documents WHERE id=?");
$q->bind_param("i",$id);
$q->execute();
$f=$q->get_result()->fetch_assoc();
$q->close();

if(!$f) exit('File not found in DB.');
$path=$f['file_path'];
if(file_exists($path)) unlink($path);

$d=$con->prepare("DELETE FROM case_documents WHERE id=?");
$d->bind_param("i",$id);
$d->execute();
$d->close();

echo "Deleted successfully.";
