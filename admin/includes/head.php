<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?></title>

  
    <!-- Tailwind CSS 
    Without this js sidebar and main content of this page not toggle and also menuToggle.js important -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
        darkMode: 'class'
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
    #main{
      margin-top:60px;
    }
  </style>
</head>
