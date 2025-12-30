/**
 * Session Management - Used across all pages
 * Maintains login state and updates UI accordingly
 */

// Check if user is logged in and update UI
async function checkAndUpdateSessionUI() {
    try {
        const response = await fetch('/NoteShare/api/check-session.php');
        const data = await response.json();
        
        if (data.loggedIn) {
            // User is logged in
            console.log('User is logged in');
        } else {
            // User is not logged in
            console.log('User is not logged in');
        }
    } catch (error) {
        console.log('Session check failed:', error);
    }
}

// Call on page load
document.addEventListener('DOMContentLoaded', checkAndUpdateSessionUI);
