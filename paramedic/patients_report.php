<?php
include '../includes/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PARAMEDIC') {
    header('Location: ../login.php');
    exit;
}

// Initialize filter variables
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '2025-05-01';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '2025-05-06';
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'DESC';

// Fetch patient outcomes with incident details
$query = "SELECT po.id, po.incident_id, i.narrative, po.patient_name, po.outcome, po.created_at
          FROM patient_outcomes po
          JOIN incidents i ON po.incident_id = i.id
          WHERE 1=1";
if ($start_date && $end_date) {
    $query .= " AND po.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}
$query .= " ORDER BY po.created_at $sort_order";
$result = mysqli_query($conn, $query);
if ($result === FALSE) {
    $error = "Error fetching patient outcomes: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Report</title>
    <style>
        .content { margin-left: 220px; padding: 20px; }
        .filter-section { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-section label { margin-right: 5px; }
        .filter-section input[type="date"], .filter-section select { padding: 5px; width: 150px; }
        .filter-section button { padding: 5px 10px; cursor: pointer; }
        .filter-section .generate-btn { background-color: #007bff; color: white; border: none; }
        .filter-section .reset-btn { background-color: #6c757d; color: white; border: none; }
        .filter-section .print-btn { background-color: #007bff; color: white; border: none; }
        .filter-section .generate-btn:hover, .filter-section .print-btn:hover { background-color: #0056b3; }
        .filter-section .reset-btn:hover { background-color: #5a6268; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; font-weight: bold; margin: 10px 0; }
        @media print {
            .sidebar, .filter-section, .error { display: none; }
            .content { margin-left: 0; }
            table { border: 2px solid #000; }
            th, td { border: 1px solid #000; }
        }
    </style>
    <script>
        function printReport() {
            const originalContent = document.body.innerHTML;
            const printContent = `
                <div class="content">
                    <h1>Patients Report</h1>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Incident ID</th>
                            <th>Narrative</th>
                            <th>Patient Name</th>
                            <th>Outcome</th>
                            <th>Created At</th>
                        </tr>
                        ${document.querySelector('table').innerHTML}
                    </table>
                </div>
            `;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
        }

        function resetFilters() {
            document.getElementById('start_date').value = '2025-05-01';
            document.getElementById('end_date').value = '2025-05-06';
            document.getElementById('sort_order').value = 'DESC';
            document.querySelector('form.filter-form').submit();
        }
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h1>Patients Report</h1>
        <?php if (isset($error)) { echo '<p class="error">' . $error . '</p>'; } ?>

        <form method="POST" action="" class="filter-form">
            <div class="filter-section">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <label for="sort_order">Sort Order:</label>
                <select id="sort_order" name="sort_order">
                    <option value="DESC" <?php if ($sort_order == 'DESC') echo 'selected'; ?>>Descending</option>
                    <option value="ASC" <?php if ($sort_order == 'ASC') echo 'selected'; ?>>Ascending</option>
                </select>
                <button type="submit" class="generate-btn">Generate Report</button>
                <button type="button" class="reset-btn" onclick="resetFilters()">Reset Filters</button>
                <button type="button" class="print-btn" onclick="printReport()">Print Report</button>
            </div>
        </form>

        <?php if (isset($error) || !$result) { ?>
            <p>No patient outcomes available or an error occurred.</p>
        <?php } else { ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Incident ID</th>
                <th>Narrative</th>
                <th>Patient Name</th>
                <th>Outcome</th>
                <th>Created At</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['incident_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['narrative']); ?></td>
                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['outcome']); ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                </tr>
            <?php } ?>
        </table>
        <?php } ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>