document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    // Array of bookings
    var bookings = [
      {
        title: 'John Doe',
        bookingDate: '2024-11-10T09:00:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-10T09:00:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-10T09:00:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-10T09:00:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-10T09:00:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-10T10:00:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-10T11:00:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-12T11:30:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-15T14:00:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-20T10:30:00'
      },
      {
        title: 'John Doe',
        bookingDate: '2024-11-22T13:00:00'
      }
    ];

    // Map the bookings to the format that FullCalendar expects for events
    var events = bookings.map(function(booking) {
      return {
        title: booking.title,
        start: booking.bookingDate
      };
    });

    // Initialize the FullCalendar
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',  // Default view is month
      editable: false,              // Prevent users from modifying events
      selectable: false,            // Disable selecting time slots
      headerToolbar: {
        left: 'prev,next today',    // Navigation buttons
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'  // Month, week, and day views
      },
      events: events,  // Use the transformed bookings array
      eventTimeFormat: { // Use AM/PM format for event times
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short'  // 'short' for 'am/pm', 'narrow' would give 'a/p'
      }
    });

    // Render the calendar
    calendar.render();
  });
