<?php
include 'db_conn.php';

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id = $_POST['id'];
    $current_status = $_POST['status'];

    // Toggle status
    $new_status = $current_status ? 0 : 1;

    $sql = "UPDATE users SET status = $new_status WHERE id = '$id'";

    if ($conn->query($sql) === TRUE) {
        $message = "User status updated successfully.";
    } else {
        $message = "Error updating status: " . $conn->error;
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = $_POST['id'];

    // Delete user history and the user
    $conn->query("DELETE FROM answers WHERE student_id = '$id'");
    $conn->query("DELETE FROM quizzes WHERE created_by = '$id'");
    $sql = "DELETE FROM users WHERE id = '$id'";

    if ($conn->query($sql) === TRUE) {
        $message = "User deleted successfully.";
    } else {
        $message = "Error deleting user: " . $conn->error;
    }
}

// Fetch users based on filters
$filter = $_POST['role_filter'] ?? 'all'; // Default to 'all'
$search_query = $_POST['searchField'] ?? ''; // Default to empty search

$query = "SELECT * FROM users WHERE role != 'admin'";
if ($filter !== 'all') {
    $query .= " AND role = '$filter'";
}
if (!empty($search_query)) {
    $query .= " AND (name LIKE '%$search_query%' OR id LIKE '%$search_query%' OR role LIKE '%$search_query%')";
}
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        form.filter-search {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            justify-content: center;
            align-items: center;
        }

        #role_filter, #searchField {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #search-bar {
            display: flex;
            position: relative;
            flex: 1;
            max-width: 400px;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 25px;
            align-items: center;
        }

        #searchField {
            flex: 1;
            border: none;
            outline: none;
            padding: 10px;
            padding-right: 30px;
        }

        #clearSearchButton {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            color: #808080;
            cursor: pointer;
        }

        #clearSearchButton:hover {
            color: #a9a9a9;
        }

        .btn {
            padding: 8px 12px;
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn-lock {
            background-color: #007BFF; /* Blue color for Lock/Unlock button */
        }

        .btn-lock:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        .btn-danger {
            background-color: #dc3545; /* Red color for Delete button */
        }

        .btn-danger:hover {
            background-color: #c82333; /* Darker red on hover */
        }

        .btn-search {
            background-color: #007BFF; /* Blue color for Search button */
        }

        .btn-search:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        table {
            width: 80%;
            max-width: 800px;
            margin: 20px auto;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f4f4f4;
        }

        .table-container {
            position: relative;
            width: 80%;
            max-width: 800px;
            margin: 20px auto;
        }

        .add-user-btn {
            position: absolute;
            bottom: -50px;
            right: 0;
            padding: 10px 20px;
            font-size: 14px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }

        .add-user-btn:hover {
            background-color: #218838;
        }

        @media (max-width: 900px) {
            form.filter-search {
                flex-direction: column;
                gap: 10px;
            }

            .add-user-btn {
                position: static;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php';
 ?>
    <?php if (!empty($message)): ?>
        <p style="color: green; text-align: center;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Filter and Search Form -->
    <form method="POST" class="filter-search">
        <select name="role_filter" id="role_filter">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="student" <?= $filter === 'student' ? 'selected' : '' ?>>Students</option>
            <option value="lecturer" <?= $filter === 'lecturer' ? 'selected' : '' ?>>Lecturers</option>
        </select>

        <div id="search-bar">
            <input type="text" id="searchField" name="searchField" placeholder="Search by Name, ID, or Role" value="<?= htmlspecialchars($search_query) ?>" autocomplete="off">
            <span id="clearSearchButton" onclick="clearSearch();">×</span>
        </div>

        <input type="submit" value="Search" class="btn btn-search">
    </form>

    <!-- User Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['role'] ?></td>
                        <td><?= $row['status'] ? 'Active' : 'Inactive' ?></td>
                        <td>
                            <!-- Lock/Unlock Form -->
                            <form method="POST" style="display:inline;" onsubmit="return confirmToggle('<?= $row['status'] ? 'Lock' : 'Unlock' ?>', '<?= $row['name'] ?>');">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="status" value="<?= $row['status'] ?>">
                                <button type="submit" name="toggle_status" class="btn btn-lock">
                                    <?= $row['status'] ? 'Lock' : 'Unlock' ?>
                                </button>
                            </form>
                            <!-- Delete Form -->
                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete('<?= $row['name'] ?>');">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <!-- Add User Button -->
        <a href="add_user.php" class="add-user-btn">Add User</a>
    </div>

    <script>
        function clearSearch() {
            document.getElementById("searchField").value = "";
        }

        function confirmToggle(action, name) {
            return confirm(`Are you sure you want to ${action} the user "${name}"?`);
        }

        function confirmDelete(name) {
            return confirm(`Are you sure you want to delete the user "${name}"? This action cannot be undone.`);
        }
    </script>
</body>
</html>
