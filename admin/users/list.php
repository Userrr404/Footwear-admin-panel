<?php
require_once '../includes/auth_check.php'; // ensure admin session
require_once '../includes/db_connections.php';

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Search-safe query
$stmt = $connection->prepare("
    SELECT * FROM users 
    WHERE username LIKE ? OR user_email LIKE ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$like = "%$search%";
$stmt->bind_param('ssii', $like, $like, $limit, $offset);
$stmt->execute();
$users = $stmt->get_result();

// Get total count for pagination
$countStmt = $connection->prepare("
    SELECT COUNT(*) as total FROM users 
    WHERE username LIKE ? OR user_email LIKE ?
");
$countStmt->bind_param('ss', $like, $like);
$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Users List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h2 class="mb-4">User Management</h2>

<!-- <form method="GET" class="mb-3">
  <div class="input-group">
    <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-primary">Search</button>
  </div>
</form> -->

<div class="d-flex justify-content-between mb-3">
  <form method="GET" class="d-flex" style="gap: 10px;">
    <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-outline-primary">Search</button>
  </form>
  <a href="add_user.php" class="btn btn-success">➕ Add User</a>
</div>


<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr>
      <th>ID</th>
      <th>Username</th>
      <th>Email</th>
      <th>Role</th>
      <th>Status</th>
      <th>Created</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if ($users->num_rows > 0): ?>
    <?php while ($user = $users->fetch_assoc()): ?>
      <tr>
        <td><?= $user['user_id'] ?></td>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= htmlspecialchars($user['user_email']) ?></td>
        <td><span class="badge bg-info text-dark"><?= ucfirst($user['role'] ?? 'Customer') ?></span></td>
        <td>
          <?php
            $status = $user['status'] ?? 'active';
            $badgeClass = match($status) {
              'active' => 'success',
              'banned' => 'danger',
              'pending' => 'warning',
              'deleted' => 'secondary',
              default => 'secondary'
            };
          ?>
          <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
        </td>
        <td><?= date("d M Y", strtotime($user['created_at'])) ?></td>
        <td>
  <a href="view_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-primary">View</a>
  <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
  <a href="delete_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>

  <?php if ($user['status'] !== 'banned'): ?>
    <a href="ban_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Ban this user?')">Ban</a>
  <?php else: ?>
    <span class="badge bg-danger">Banned</span>
  <?php endif; ?>
</td>

      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="7" class="text-center">No users found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<!-- Pagination -->
<nav>
  <ul class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
        <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>

</body>
</html>
