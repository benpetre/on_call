<?php
require_once "../../../core/auth.php";
require_login();
require_role(['admin']);
require_once "../../../core/db.php";

/**********************************
 * CONFIGURATION
 **********************************/
$allowed_sort = [
    'first_name', 'last_name', 'email', 'role',
    'provider_type', 'call_group', 'fte', 'active'
];

$sort = $_GET['sort'] ?? 'last_name';
$dir  = $_GET['dir'] ?? 'asc';
$search = trim($_GET['search'] ?? '');
$filter_role = $_GET['filter_role'] ?? '';
$filter_ptype = $_GET['filter_ptype'] ?? '';
$filter_cg = $_GET['filter_cg'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;

/**********************************
 * VALIDATE SORTING INPUT
 **********************************/
if (!in_array($sort, $allowed_sort)) {
    $sort = 'last_name';
}

$dir = ($dir === 'desc') ? 'desc' : 'asc';
$next_dir = ($dir === 'asc') ? 'desc' : 'asc';

/**********************************
 * BUILD SQL WHERE CLAUSE
 **********************************/
$where = "WHERE 1=1";
$params = [];
$types = "";

// Search by name/email
if ($search !== "") {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

// Filter by role
if ($filter_role !== "") {
    $where .= " AND role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

// Filter by provider_type
if ($filter_ptype !== "") {
    $where .= " AND provider_type = ?";
    $params[] = $filter_ptype;
    $types .= "s";
}

// Filter by call_group
if ($filter_cg !== "") {
    $where .= " AND call_group = ?";
    $params[] = $filter_cg;
    $types .= "s";
}

// Active / inactive
if ($filter_status !== "") {
    $where .= " AND active = ?";
    $params[] = intval($filter_status);
    $types .= "i";
}

/**********************************
 * PAGINATION COUNT
 **********************************/
$count_sql = "SELECT COUNT(*) FROM oneCall_users $where";
$stmt = $conn->prepare($count_sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_rows);
$stmt->fetch();
$stmt->close();

$total_pages = max(1, ceil($total_rows / $per_page));
$offset = ($page - 1) * $per_page;

/**********************************
 * FETCH SORTED + FILTERED + PAGED DATA
 **********************************/
$sql = "
    SELECT id, first_name, last_name, email, role,
           provider_type, call_group, fte, active
    FROM oneCall_users
    $where
    ORDER BY $sort $dir, last_name ASC, first_name ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

if ($types === "") {
    $stmt->bind_param("ii", $per_page, $offset);
} else {
    $full_types = $types . "ii";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->bind_param($full_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>

<title>Admin – Users</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/on_call_dev/assets/css/main.css">

<style>
    .filter-bar {
        margin-bottom: 20px;
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .filter-bar input[type="text"] {
        width: 200px;
    }
    .pagination {
        margin-top: 20px;
        display: flex;
        gap: 8px;
    }
    .pagination a {
        padding: 6px 12px;
        background: #eaeaea;
        border-radius: 6px;
        text-decoration: none;
        color: #333;
    }
    .pagination .active-page {
        background: #1a4e8a;
        color: #fff;
    }
    .sortable-header a {
        color: #1a4e8a;
        text-decoration: none;
        font-weight: 600;
    }
    .sortable-header a:hover {
        text-decoration: underline;
    }
</style>

<script>
// Live Search — instant AJAX results
function liveSearch() {
    const searchTerm = document.getElementById('searchBox').value;

    const url = new URL(window.location.href);
    url.searchParams.set("search", searchTerm);
    url.searchParams.set("page", 1);

    window.location.href = url.toString();
}
</script>

</head>
<body>

<div class="navbar">
    <div><strong>On-Call Scheduler – Admin</strong></div>
    <div>
        <span><?= $_SESSION['name'] ?></span>
        <a href="/on_call_dev/pages/dashboard.php">Dashboard</a>
        <a href="/on_call_dev/auth/logout.php">Logout</a>
    </div>
</div>


<div class="container">

    <div class="flex-space">
        <h2>Manage Users</h2>
        <a class="btn btn-primary" href="add_user.php">+ Add New User</a>
    </div>

    <!-- FILTER BAR -->
    <div class="card" style="padding: 15px;">
        <div class="filter-bar">

            <input type="text" id="searchBox" placeholder="Search name/email..."
                   value="<?= htmlspecialchars($search) ?>"
                   onkeyup="liveSearch()">

            <form method="GET" style="display:flex; gap:10px;">

                <select name="filter_role" onchange="this.form.submit()">
                    <option value="">Role</option>
                    <option value="employed"     <?= $filter_role=='employed'?'selected':'' ?>>Employed</option>
                    <option value="non_employed" <?= $filter_role=='non_employed'?'selected':'' ?>>Non-employed</option>
                    <option value="admin"        <?= $filter_role=='admin'?'selected':'' ?>>Admin</option>
                </select>

                <select name="filter_ptype" onchange="this.form.submit()">
                    <option value="">Provider Type</option>
                    <option value="surgeon_md"       <?= $filter_ptype=='surgeon_md'?'selected':'' ?>>Surgeon MD</option>
                    <option value="non_surgeon_md"   <?= $filter_ptype=='non_surgeon_md'?'selected':'' ?>>Non-Surgeon MD</option>
                    <option value="app"              <?= $filter_ptype=='app'?'selected':'' ?>>APP</option>
                </select>

                <select name="filter_cg" onchange="this.form.submit()">
                    <option value="">Call Group</option>
                    <option value="luminis_er"        <?= $filter_cg=='luminis_er'?'selected':'' ?>>Luminis ER</option>
                    <option value="luminis_backup"    <?= $filter_cg=='luminis_backup'?'selected':'' ?>>Backup</option>
                    <option value="practice_call"     <?= $filter_cg=='practice_call'?'selected':'' ?>>Practice</option>
                    <option value="non_employed_er"   <?= $filter_cg=='non_employed_er'?'selected':'' ?>>Non-Employed ER</option>
                    <option value="none"              <?= $filter_cg=='none'?'selected':'' ?>>None</option>
                </select>

                <select name="filter_status" onchange="this.form.submit()">
                    <option value="">Status</option>
                    <option value="1" <?= $filter_status==='1'?'selected':'' ?>>Active</option>
                    <option value="0" <?= $filter_status==='0'?'selected':'' ?>>Inactive</option>
                </select>

            </form>

        </div>
    </div>


    <!-- USER TABLE -->
    <table class="table">
        <thead>
            <tr>
                <th class="sortable-header">
                    <a href="?sort=last_name&dir=<?= $next_dir ?>">Name</a>
                </th>
                <th class="sortable-header">
                    <a href="?sort=role&dir=<?= $next_dir ?>">Role</a>
                </th>
                <th class="sortable-header">
                    <a href="?sort=provider_type&dir=<?= $next_dir ?>">Provider Type</a>
                </th>
                <th class="sortable-header">
                    <a href="?sort=call_group&dir=<?= $next_dir ?>">Call Group</a>
                </th>
                <th class="sortable-header">
                    <a href="?sort=fte&dir=<?= $next_dir ?>">FTE</a>
                </th>
                <th class="sortable-header">
                    <a href="?sort=active&dir=<?= $next_dir ?>">Status</a>
                </th>
                <th style="width:180px;">Actions</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['last_name'] . ", " . $row['first_name']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td><?= htmlspecialchars($row['provider_type']) ?></td>
                <td><?= htmlspecialchars($row['call_group']) ?></td>
                <td><?= htmlspecialchars($row['fte']) ?></td>
                <td><?= $row['active'] ? 'Active' : 'Inactive' ?></td>

                <td>
                    <a href="edit_user.php?id=<?= $row['id'] ?>">Edit</a> |
                    <a href="list_users.php?toggle_active=<?= $row['id'] ?>"
                       onclick="return confirm('Toggle active status for this user?');">
                       <?= $row['active'] ? 'Deactivate' : 'Activate' ?>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>


    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <a href="?page=<?= $p ?>&sort=<?= $sort ?>&dir=<?= $dir ?>&search=<?= urlencode($search) ?>"
               class="<?= ($p == $page ? 'active-page' : '') ?>">
               <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

</div>

</body>
</html>

