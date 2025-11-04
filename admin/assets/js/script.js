//employee-user login
const btns = document.querySelectorAll('.pagebtn');
const frames = document.querySelectorAll('.frames');

var frameActive = function (manual) {
  btns.forEach((btn) => {
    btn.classList.remove('active');
  });
  frames.forEach((slide) => {
    slide.classList.remove('active');
  });

  btns[manual].classList.add('active');
  frames[manual].classList.add('active');
};

btns.forEach((btn, i) => {
  btn.addEventListener('click', () => {
    frameActive(i);
  });
});

// Menu Toggle Functionality
const menuToggle = document.getElementById('menuToggle');
const sidebarClose = document.getElementById('sidebarClose');
const sidebar = document.getElementById('sidebar');
const body = document.body;

// Function to toggle sidebar
function toggleSidebar() {
  sidebar.classList.toggle('hidden');
  body.classList.toggle('sidebar-hidden');
  
  // Save state to localStorage
  const isHidden = sidebar.classList.contains('hidden');
  localStorage.setItem('sidebarHidden', isHidden);
  
  updateToggleIcon(isHidden);
}

// Load saved state from localStorage
const sidebarState = localStorage.getItem('sidebarHidden');
if (sidebarState === 'true') {
  sidebar.classList.add('hidden');
  body.classList.add('sidebar-hidden');
  updateToggleIcon(true);
}

// Toggle from header button
menuToggle.addEventListener('click', () => {
  toggleSidebar();
});

// Close from sidebar button
sidebarClose.addEventListener('click', () => {
  toggleSidebar();
});

function updateToggleIcon(isHidden) {
  const icon = menuToggle.querySelector('i');
  if (isHidden) {
    icon.className = 'fas fa-bars';
    menuToggle.setAttribute('title', 'Hiện menu');
  } else {
    icon.className = 'fas fa-bars';
    menuToggle.setAttribute('title', 'Ẩn menu');
  }
}