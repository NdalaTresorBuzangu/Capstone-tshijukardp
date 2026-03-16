// Function to show specific section based on button clicked
function showSection(sectionId) {
    // Hide all sections first
    const sections = document.querySelectorAll('.section');
    sections.forEach(section => section.style.display = 'none');

    // Display the selected section
    const targetSection = document.getElementById(sectionId);
    targetSection.style.display = 'block';
}

// Function to simulate tracking document progress by ID
function trackDocument() {
    const documentId = document.getElementById('documentId').value;
    const documentStatus = document.getElementById('documentStatus');

    // Placeholder logic for tracking (can be replaced with real backend data)
    if (documentId === "123") {
        documentStatus.innerText = "Status: In Progress";
    } else if (documentId === "456") {
        documentStatus.innerText = "Status: Resolved";
    } else if (documentId === "789") {
        documentStatus.innerText = "Status: Pending";
    } else {
        documentStatus.innerText = "Document not found. Please check the ID and try again.";
    }
}
