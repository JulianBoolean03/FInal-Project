function applySavedTheme() {
  const savedTheme = localStorage.getItem('selectedTheme') || 'theme-classic';
  document.body.classList.remove('theme-classic', 'theme-snowy', 'theme-candycane');
  document.body.classList.add(savedTheme);
  
  // Update active button
  document.querySelectorAll('.theme-options button').forEach(btn => {
    btn.classList.toggle('active', btn.getAttribute('data-theme') === savedTheme);
  });
}

function setupThemeButtons() {
  document.querySelectorAll('.theme-options button').forEach(btn => {
    btn.addEventListener('click', () => {
      const selectedTheme = btn.getAttribute('data-theme');
      
      // Remove all theme classes
      document.body.classList.remove('theme-classic', 'theme-snowy', 'theme-candycane');
      
      // Apply selected theme
      document.body.classList.add(selectedTheme);
      
      // Save preference
      localStorage.setItem('selectedTheme', selectedTheme);
      
      // Update active button
      document.querySelectorAll('.theme-options button').forEach(b => {
        b.classList.toggle('active', b === btn);
      });
    });
  });
}

// Run on page load
document.addEventListener('DOMContentLoaded', () => {
  applySavedTheme();
  setupThemeButtons();
});
