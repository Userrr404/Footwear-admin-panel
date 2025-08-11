<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClauses = [];
$params = [];
$types = '';

// Search in username or email
if (!empty($search)) {
    $whereClauses[] = "(username LIKE ? OR user_email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Filter by role
if (!empty($roleFilter)) {
    $whereClauses[] = "role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

// Filter by status
if (!empty($statusFilter)) {
    $whereClauses[] = "status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Main query
$query = "SELECT * FROM users $whereSQL ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $connection->prepare($query);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Count query
$countQuery = "SELECT COUNT(*) as total FROM users $whereSQL";
$countStmt = $connection->prepare($countQuery);
$countParams = $params;
array_pop($countParams); // remove offset
array_pop($countParams); // remove limit
$countTypes = substr($types, 0, -2);
if (!empty($countTypes)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);
?>


<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Users Management</title>
  <style>
    #main{
      margin-top:30px;
    }
  </style>
  <script src="https://cdn.tailwindcss.com"></script>
  
  <script>
    tailwind.config = {
      darkMode: 'class'
    };
  </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-white min-h-screen">

<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div id="main" class="ml-64 p-6 transition-all duration-300">

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-4xl font-semibold tracking-tight">User Management</h1>
    <a href="add_user.php" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
      ➕ Add User
    </a>
  </div>

  <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name/email"
      class="p-2 rounded border focus:outline-none focus:ring-2 focus:ring-blue-500">

    <select name="role" class="p-2 rounded border focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">All Roles</option>
      <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="manager" <?= $roleFilter === 'manager' ? 'selected' : '' ?>>Manager</option>
      <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>>Customer</option>
    </select>

    <select name="status" class="p-2 rounded border focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">All Statuses</option>
      <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="banned" <?= $statusFilter === 'banned' ? 'selected' : '' ?>>Banned</option>
      <option value="deleted" <?= $statusFilter === 'deleted' ? 'selected' : '' ?>>Deleted</option>
    </select>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Search</button>
  </form>

  <div class="overflow-x-auto shadow rounded-lg">
    <table class="w-full text-sm text-left border border-gray-200 dark:border-gray-700">
      <thead class="bg-gray-100 dark:bg-gray-800 dark:text-white text-gray-600 uppercase text-xs">
        <tr>
          <th class="p-3">ID</th>
          <th class="p-3">Username</th>
          <th class="p-3">Email</th>
          <th class="p-3">Role</th>
          <th class="p-3">Status</th>
          <th class="p-3">Created</th>
          <th class="p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php if ($users->num_rows > 0): ?>
          <?php while ($user = $users->fetch_assoc()): ?>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="p-3"><?= $user['user_id'] ?></td>
              <td class="p-3 font-medium"><?= htmlspecialchars($user['username']) ?></td>
              <td class="p-3"><?= htmlspecialchars($user['user_email']) ?></td>
              <td class="p-3">
                <span class="inline-block px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                  <?= ucfirst($user['role'] ?? 'Customer') ?>
                </span>
              </td>
              <td class="p-3">
                <?php
                  $status = $user['status'] ?? 'active';
                  $statusStyles = [
                    'active' => 'bg-green-100 text-green-800',
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'banned' => 'bg-red-100 text-red-800',
                    'deleted' => 'bg-gray-200 text-gray-800',
                  ];
                  $class = $statusStyles[$status] ?? 'bg-gray-200 text-gray-800';
                ?>
                <span class="inline-block px-2 py-1 text-xs font-semibold rounded <?= $class ?>">
                  <?= ucfirst($status) ?>
                </span>
              </td>
              <td class="p-3"><?= date("d M Y", strtotime($user['created_at'])) ?></td>
              <td class="p-3 text-center space-x-2">
                <a href="view_user.php?id=<?= $user['user_id'] ?>" class="text-blue-600 hover:underline" title="View">👁️</a>
                <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="text-yellow-600 hover:underline" title="Edit">✏️</a>
                <a href="delete_user.php?id=<?= $user['user_id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Delete this user?')" title="Delete">🗑️</a>
                <?php if ($user['status'] !== 'banned'): ?>
                  <a href="ban_user.php?id=<?= $user['user_id'] ?>" class="text-red-400 hover:text-red-600" onclick="return confirm('Ban this user?')" title="Ban">🚫</a>
                <?php else: ?>
                  <span title="User Banned">🔒</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center py-4 text-gray-500">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="mt-6 flex justify-center">
    <nav class="inline-flex space-x-1">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>&page=<?= $i ?>"
          class="px-3 py-1 border rounded <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </nav>
  </div>

</div>

<script src="../assets/js/menuToggle.js"></script>
</body>
</html>
