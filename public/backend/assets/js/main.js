$(document).ready(function () {
  $(".menu-icon").click(function () {
    $(".sidebar").toggleClass("active");
    $("body").toggleClass("no-scroll");
  });

  $(".close-sidebar-icon").click(function () {
    $(".sidebar").removeClass("active");
    $("body").toggleClass("no-scroll");
  });

  // Close the sidebar if clicking outside of it
  $(document).click(function (event) {
    // Check if the click is outside the sidebar and the menu button
    if (!$(event.target).closest(".sidebar, .menu-icon").length) {
      if ($(".sidebar").hasClass("active")) {
        $(".sidebar").removeClass("active");
        $("body").removeClass("no-scroll");
      }
    }
  });

  // // nice select start
  // $(".select").niceSelect();
  // // nice select end

  // counter animation start
  const counterItems = document.querySelectorAll(".count");
  if (counterItems.length > 0) {
    let speed = 2000;
    counterItems.forEach((counterItem) => {
      // const current = counterItem.querySelector("span");
      const endValue = +counterItem.dataset.target;

      let startValue = endValue > 100 ? endValue - 500 : 0;

      const duration = Math.floor(speed / endValue);

      const counter = setInterval(() => {
        startValue++;
        // current.textContent = startValue.toLocaleString("en-US");
        counterItem.textContent = startValue.toLocaleString("en-US");
        if (startValue === endValue) {
          clearInterval(counter);
        }
      }, duration);
    });
  }
  // counter animation end

  // choose item select start
  const chooseItems = document.querySelectorAll(".choose-item");
  chooseItems.forEach((item) => {
    item.addEventListener("click", function () {
      if (item.classList.contains("active")) {
        item.classList.remove("active");
      } else {
        chooseItems.forEach((subItem) => {
          subItem.classList.remove("active");
        });
        item.classList.add("active");
        const link = item.dataset.link;
        window.location.href = link;
      }
    });
  });
  // choose item select end

  // for profile toggle
  const profileBtn = document.querySelector(".profile-dropdown-btn");
  const profileDropdown = document.querySelector(".profile-dropdown");
  const notificationBtn = document.querySelector(".notification-btn");
  const notificationDropdown = document.querySelector(".notification-dropdown");

  // Function to toggle the dropdown
  profileBtn.addEventListener("click", (event) => {
    event.stopPropagation(); // Prevent click event from bubbling to the document
    profileDropdown.classList.toggle("show"); // Toggle visibility
    notificationDropdown.classList.remove("show"); // Toggle visibility
  });

  // Function to hide the dropdown when clicking outside
  document.addEventListener("click", (event) => {
    if (!profileDropdown.contains(event.target)) {
      profileDropdown.classList.remove("show"); // Hide dropdown
    }
  });

  // Optional: Prevent dropdown from closing if clicked inside
  profileDropdown.addEventListener("click", (event) => {
    event.stopPropagation(); // Prevent click event from bubbling to the document
  });

  // for notification toggle

  // Function to toggle the dropdown
  notificationBtn.addEventListener("click", (event) => {
    event.stopPropagation(); // Prevent click event from bubbling to the document
    notificationDropdown.classList.toggle("show"); // Toggle visibility
    profileDropdown.classList.remove("show"); // Toggle visibility
  });

  // Function to hide the dropdown when clicking outside
  document.addEventListener("click", (event) => {
    if (!notificationDropdown.contains(event.target)) {
      notificationDropdown.classList.remove("show"); // Hide dropdown
    }
  });

  // Optional: Prevent dropdown from closing if clicked inside
  notificationDropdown.addEventListener("click", (event) => {
    event.stopPropagation(); // Prevent click event from bubbling to the document
  });

  // for inbox
  // for hide inbox
  document.querySelectorAll(".inbox-messages .item").forEach((item) => {
    item.addEventListener("click", () => {
      const writeMessageContainer = document.querySelector(".user-messages");

      if (window.innerWidth <= 650) {
        document.querySelector(".inbox").style.display = "none";
        writeMessageContainer.style.display = "block";
      }
    });
  });

  // document.querySelector(".back-to-inbox-btn").addEventListener("click", () => {
  //   document.querySelector(".user-messages").style.display = "none";
  //   document.querySelector(".inbox").style.display = "block";
  // });
});
