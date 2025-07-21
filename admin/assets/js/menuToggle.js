// menuToggle.js
document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const main = document.getElementById("main");

  toggleBtn.addEventListener("click", () => {
    const isSidebarHidden = sidebar.classList.toggle("hidden");

    // Switch icon based and margin on sidebar visibility
    if (isSidebarHidden) {
      main.classList.remove("ml-60");
      main.classList.add("ml-[0]");
      toggleBtn.classList.remove("fa-times");
      toggleBtn.classList.add("fa-bars");
    } else {
      main.classList.remove("ml-[0]");
      main.classList.add("ml-60");
      toggleBtn.classList.remove("fa-bars");
      toggleBtn.classList.add("fa-times");
    }
  });
});
