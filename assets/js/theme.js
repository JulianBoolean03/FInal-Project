function applySavedTheme() {
    const savedTheme = localStorage.getItem('selectedTheme') || 'theme-classic';
    document.body.classList.remove('theme-classic', 'theme-snowy', 'theme-candycane');
    document.body.classList.add(savedTheme);
}

// Run on page load
document.addEventListener('DOMContentLoaded', applySavedTheme);
