<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$serverName = "127.0.0.1";
$userName = "";
$userPassword = "";
$dbName = "sbj";

$connectionInfo = array(
    "Database" => $dbName,
    "UID" => $userName,
    "PWD" => $userPassword,
    "MultipleActiveResultSets" => true
);

$connect = sqlsrv_connect($serverName, $connectionInfo);

if ($connect === FALSE) {
    die(print_r(sqlsrv_errors(), true));
}

// SQL query to round AvgColumn4 and format the date
$stmt = "SELECT MachineName, 
                 CAST(FORMAT(CONVERT(DATETIME, Column3), 'yyyy-MM-dd') AS DATE) AS Date, 
                 DATEPART(HOUR, CONVERT(DATETIME, Column3)) AS Hour, 
                 ROUND(AVG(CAST(Column4 AS FLOAT)), 0) AS AvgColumn4
          FROM sbj.dbo.SbjData02 
          GROUP BY MachineName, CAST(FORMAT(CONVERT(DATETIME, Column3), 'yyyy-MM-dd') AS DATE), DATEPART(HOUR, CONVERT(DATETIME, Column3))
          ORDER BY MachineName, Date, Hour";

$query = sqlsrv_query($connect, $stmt);

if ($query === FALSE) {
    die(print_r(sqlsrv_errors(), true));
}

// Function to create and download CSV file
function download_csv($query) {
    $csvFileName = "SBJbyHour_" . date("Y-m-d_H-i-s") . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="'.$csvFileName.'"');

    $fp = fopen('php://output', 'w');

    // Write the header row
    fputcsv($fp, array('MachineName', 'Date', 'Hour', 'AvgColumn4'));

    // Write the data rows
    while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
        // Convert the DateTime object to a string
        if (isset($row['Date']) && $row['Date'] instanceof DateTime) {
            $row['Date'] = $row['Date']->format('Y-m-d');
        }
        fputcsv($fp, $row);
    }

    fclose($fp);

    // Terminate script to prevent any additional output
    exit();
}

// Check if 'download' parameter is set in the URL
if (isset($_GET['download'])) {
    download_csv($query);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Data Export</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <div class="row">
        <h1>Data Table</h1>
        <a href="?download=true" class="btn btn-primary">Download CSV</a>
        <table class="table table-condensed table-striped table-bordered mt-3">
            <thead>
            <tr>
                <th style="text-align: center">MachineName</th>
                <th style="text-align: center">Date</th>
                <th style="text-align: center">Hour</th>
                <th style="text-align: center">AvgColumn4</th>
            </tr>
            </thead>
            <tbody>
            <?php
            while ($result = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($result["MachineName"]); ?></td>
                    <td><?php echo htmlspecialchars($result["Date"]->format('Y-m-d')); ?></td>
                    <td><?php echo htmlspecialchars($result["Hour"]); ?></td>
                    <td><?php echo htmlspecialchars($result["AvgColumn4"]); ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
