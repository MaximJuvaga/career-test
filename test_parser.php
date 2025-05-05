<?php
// test_parser.php
$data = file_get_contents("php/get_uni_programs.php");
echo "<pre>";
print_r(json_decode($data, true));
echo "</pre>";
?>