<!-- header.php -->
<header class="fixed flex items-center px-4 py-2 bg-white dark:bg-gray-800 text-dark dark:text-white fixed top-0 w-full z-50 shadow-sm">

    <button id="toggleSidebar" class="text-2xl font-bold focus:outline-none absolute left-4">
        <i id="toggleIcon" class="fas fa-times"></i>
    </button>
    
    <h1 class="mx-auto text-xl font-bold text-blue-600 dark:text-blue-400">👟 Footwear Admin Dashboard</h1>

    <div class="flex items-center gap-4 absolute right-4">
      <div class="text-sm text-gray-600 dark:text-gray-300">
        Welcome, <span class="font-semibold text-gray-800 dark:text-white"><?= $_SESSION['admin_name'] ?></span>
      </div>
      <button onclick="toggleDarkMode()" class="hover:text-blue-600 text-xl" title="Toggle Dark Mode">🌓</button>
    </div>
</header>

<link rel="stylesheet" href="../assets/css/menuToggle.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
  header{
    height: 60px;
  }

/* ===== Responsive for Large Screens (576px – 767px) ===== */
@media (min-width: 576px) and (max-width: 767px){
  header { padding: 0.5rem 1rem; }
  header h1 { font-size: 18px !important; margin-left: 60px !important;}
  header .text-sm { font-size: 14px; }
  #toggleSidebar { font-size: 24px; }
}  

/* ===== Responsive for Small Tablets (480px – 575px) ===== */
@media (min-width: 480px) and (max-width: 575px) {
    header { padding: 0.5rem 1rem; }
    header h1 { font-size: 16px; margin-left: 140px;}
    header .text-sm { font-size: 14px; }
    header .flex.items-center.gap-4 {
    margin-bottom: 30px;
  }
    #toggleSidebar { font-size: 24px; }
  }

/* ===== Responsive for Medium Phones (425px – 479px) ===== */
@media (min-width: 425px) and (max-width: 479px) {
  header { padding: 0.5rem 1rem; }
  header h1 { font-size: 14px; }
  header .text-sm { font-size: 12px; }
  header .flex.items-center.gap-4 {
    margin-bottom: 30px;
  }
  #toggleSidebar { font-size: 20px; }
}
/* ===== Responsive for Small Phones (<425px) Optional ===== */
@media (max-width: 424px) {
  header {height: 60px; padding: 0.4rem 0.8rem; }
  header h1 { display : none; }
  header .flex.items-center.gap-4 {
    flex-direction: row;
    gap: 0.2rem !important;
  }
  #toggleSidebar { font-size: 18px; }
}
</style>

<script>
  function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', 
         document.documentElement.classList.contains('dark') ? 'dark' : 'light'
    );
  }

  (function () {
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark');
    }
  })();
</script>
