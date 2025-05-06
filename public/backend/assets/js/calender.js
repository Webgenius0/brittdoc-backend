const picker = new easepick.create({
    element: document.getElementById("date-container"),
    css: [
        './assets/css/plugins/easepick.css',
    ],
    zIndex: 10, // Ensure the picker appears above other elements
    calendars: 1, // Show one calendar
    grid: 1,
    plugins: ["RangePlugin"],
    RangePlugin: {
        elementEnd: document.getElementById("datepicker-to"),
        calendars: 1, // Show one calendar for the end picker as well
    },
});

// Optional: Sync the pickers to reflect selected dates in both fields
picker.on("select", (e) => {
    const startDate = e.detail.start; // Get start date
    const endDate = e.detail.end;     // Get end date

    // Check if both dates are selected
    // if (startDate && endDate) {
    //     const startFormatted = formatDate(startDate);
    //     const endFormatted = formatDate(endDate);

    //     // Update the date-text span
    //     document.getElementById("date-text").textContent = `${startFormatted} - ${endFormatted}`;
    // }
});

// Helper function to format the date in "MMM dd" format (e.g., Apr 11)
function formatDate(date) {
    const options = { month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

