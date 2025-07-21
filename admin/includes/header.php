<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/menuToggle.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <!-- header.php -->
<header class="d-flex justify-content-between align-items-center px-4 py-2 bg-white text-dark fixed top-0 w-full z-50 shadow-sm">
    <div>
            <i id="menu-toggle" class="fas fa-times fa-lg cursor-pointer"></i>
        </div>
    
      <h1 class="text-xl font-bold text-blue-600 dark:text-blue-400">👟 Footwear Admin Dashboard</h1>

    <div class="flex items-center gap-4">
      <div class="text-sm text-gray-600 dark:text-gray-300">
        Welcome, <span class="font-semibold text-gray-800 dark:text-white"><?= $_SESSION['admin_name'] ?></span>
      </div>
      <button onclick="toggleDarkMode()" class="hover:text-blue-600 text-xl" title="Toggle Dark Mode">🌓</button>
    </div>
</header>

<script>
  function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
  }

  (function () {
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  })();

</script>

