function showToast(message, duration = 3000) {
    // Create toast element if it doesn't exist
    let toast = document.getElementById("toast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "toast";
        document.body.appendChild(toast);
    }

    // Set toast content and styles
    toast.textContent = message;
    toast.classList.add("show");

    // Hide the toast after the specified duration
    setTimeout(() => {
        toast.classList.remove("show");
    }, duration);
}

// Make the function globally available
window.showToast = showToast;