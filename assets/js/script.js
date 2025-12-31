/* -----------------------------------------------------------
   AGENT LEAD MANAGER - MASTER SCRIPT
   Handles modals, popups, and link generation redirects.
----------------------------------------------------------- */

document.addEventListener('DOMContentLoaded', function() {

    // Variable to track which user we are currently processing
    let currentLeadId = 0;

    /**
     * OPEN THE ORDER MODAL
     * Called when Agent clicks "+ Order" button
     */
    window.openOrderModal = function(id, name) {
        // 1. Find the modal elements
        const modal = document.getElementById('almOrderModal');
        const nameLabel = document.getElementById('modalCustomerName');
        const urlInput = document.getElementById('productUrlInput');

        if (!modal) {
            console.error('Error: Modal element not found in HTML.');
            return;
        }

        // 2. Set the data
        currentLeadId = id;
        if (nameLabel) nameLabel.innerText = name;
        
        // 3. Clear previous input
        if (urlInput) urlInput.value = '';

        // 4. Show the modal
        modal.style.display = "block";
    };

    /**
     * CLOSE THE MODAL
     */
    window.closeOrderModal = function() {
        const modal = document.getElementById('almOrderModal');
        if (modal) {
            modal.style.display = "none";
        }
    };

    /**
     * GENERATE LINK ACTION
     * Called when Agent clicks "Generate & Save Order" inside the modal
     */
    window.generateLink = function() {
        const urlInput = document.getElementById('productUrlInput');
        
        // Validation
        if (!urlInput || !urlInput.value) {
            alert("Please paste a product URL first!");
            return;
        }

        const rawUrl = urlInput.value;

        // Construct the URL to trigger our PHP backend
        // This reloads the page with specific parameters
        const finalLink = '?alm_action=generate_link&lead_id=' + currentLeadId + '&url=' + encodeURIComponent(rawUrl);
        
        // Go!
        window.location.href = finalLink;
    };

    /**
     * CLICK OUTSIDE TO CLOSE
     * If user clicks the dark background area, close the modal
     */
    window.onclick = function(event) {
        const modal = document.getElementById('almOrderModal');
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };

});