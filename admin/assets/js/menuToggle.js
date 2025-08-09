const sidebar    = document.getElementById("sidebar");
const main       = document.getElementById("main");
const toggleBtn  = document.getElementById("toggleSidebar");
const toggleIcon = document.getElementById("toggleIcon");

function setInitialState(){
  if(window.innerWidth <= 767){
    sidebar.classList.add("hidden");
    toggleIcon.classList.remove("fa-times");
    toggleIcon.classList.add("fa-bars");
    main.classList.remove("ml-60");
    main.classList.add("ml-0");
  } else {
    sidebar.classList.remove("hidden");
    toggleIcon.classList.remove("fa-bars");
    toggleIcon.classList.add("fa-times");
    main.classList.add("ml-60");
    main.classList.remove("ml-0");
  }
}

window.addEventListener("load", setInitialState);
window.addEventListener("resize", setInitialState);

toggleBtn.addEventListener("click", () => {
  sidebar.classList.toggle("hidden");
  if (sidebar.classList.contains("hidden")) {
    toggleIcon.classList.remove("fa-times");
    toggleIcon.classList.add("fa-bars");
    main.classList.remove("ml-60");
    main.classList.add("ml-0");
  } else {
    toggleIcon.classList.remove("fa-bars");
    toggleIcon.classList.add("fa-times");
    main.classList.add("ml-60");
    main.classList.remove("ml-0");
  }
});
