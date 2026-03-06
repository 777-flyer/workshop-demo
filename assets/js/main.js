/*
 * main.js — AutoCare Workshop
 *
 * This file does three things:
 *   1. Calendar  — fetch slot data from the server and draw it
 *   2. Booking form — mechanic card click, basic field checks
 *   3. Admin modal  — open/close the edit popup
 *
 * Reading tip: start at the bottom ("START HERE") and work upward.
 */


/* ============================================================
   PART 1 — CALENDAR
   ============================================================
   How it works in plain English:
     - The server has a PHP file (api/availability.php) that returns
       JSON data about which mechanics are free on which days.
     - When the page loads, JavaScript asks for this month's data.
     - When the data arrives, we draw the calendar grid.
     - When the user clicks Prev/Next, we ask for a different month.
   ============================================================ */

// These two variables remember which month we are showing.
// They live outside any function so every function can read them.
var currentYear  = 0;
var currentMonth = 0;  // 1 = January, 2 = February, ... 12 = December

// This object stores the slot data we received from the server.
// Shape: { "2025-03-15": [ {mechanic_id, name, free_slots}, ... ], ... }
var slotData = {};

// Month names — used to show "March 2025" in the calendar header
var MONTH_NAMES = [
    "January", "February", "March",    "April",
    "May",     "June",     "July",     "August",
    "September","October", "November", "December"
];

// Day labels across the top of the calendar
var DAY_LABELS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];


// ----------------------------------------------------------
// Step 1: called once when the page loads.
// Sets the current month and draws the calendar.
// ----------------------------------------------------------
function initCalendar() {

    // Check if a calendar exists on this page.
    // Not every page has one — only index.php and book.php.
    var calendarDiv = document.getElementById("availability-calendar");
    if (calendarDiv === null) {
        return; // no calendar on this page, stop here
    }

    // Get today's year and month to start with
    var today   = new Date();
    currentYear  = today.getFullYear();   // e.g. 2025
    currentMonth = today.getMonth() + 1; // getMonth() is 0-based, we want 1-based

    // Draw the calendar structure (empty cells first)
    drawCalendar();

    // Ask the server for this month's slot data
    fetchSlotData();

    // When the user clicks "Prev" — go back one month
    document.getElementById("cal-prev").addEventListener("click", function () {
        currentMonth = currentMonth - 1;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear  = currentYear - 1;
        }
        slotData = {}; // clear old data so the calendar shows loading state
        drawCalendar();
        fetchSlotData();
    });

    // When the user clicks "Next" — go forward one month
    document.getElementById("cal-next").addEventListener("click", function () {
        currentMonth = currentMonth + 1;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear  = currentYear + 1;
        }
        slotData = {};
        drawCalendar();
        fetchSlotData();
    });
}


// ----------------------------------------------------------
// Step 2: Ask the server for slot data via AJAX.
//
// AJAX means: send an HTTP request in the background,
// without reloading the page, and handle the response
// when it comes back.
//
// fetch() sends the request. .then() runs when the response arrives.
// ----------------------------------------------------------
function fetchSlotData() {

    // Show a small loading message while we wait
    var grid = document.getElementById("cal-grid");
    if (grid) {
        grid.innerHTML = "<p style='padding:20px; color:#888;'>Loading...</p>";
    }

    // Build the URL we are requesting.
    // e.g. "api/availability.php?year=2025&month=3"
    var url = "api/availability.php?year=" + currentYear + "&month=" + currentMonth;

    // fetch() sends the HTTP GET request.
    // It returns a "Promise" — a way to say "do this, and when it's done,
    // call this function with the result."
    fetch(url)

        // .then(response => response.json()) means:
        // "when the HTTP response arrives, parse its body as JSON text"
        .then(function (response) {
            return response.json(); // converts the JSON string into a JS object
        })

        // .then(data => ...) means:
        // "when JSON parsing is done, the parsed data arrives here"
        .then(function (data) {
            slotData = data;  // save the data so drawCalendar() can use it
            drawCalendar();   // redraw the calendar now that we have real data
        })

        // .catch() runs if anything above failed (network error, bad JSON, etc.)
        .catch(function () {
            if (grid) {
                grid.innerHTML = "<p style='padding:20px; color:#888;'>Could not load availability data.</p>";
            }
        });
}


// ----------------------------------------------------------
// Step 3: Draw the calendar grid into the #cal-grid div.
//
// We build a string of HTML and set it all at once.
// This is simpler than creating each div one by one.
// ----------------------------------------------------------
function drawCalendar() {

    // Update the heading: "March 2025"
    var heading = document.getElementById("cal-heading");
    if (heading) {
        heading.textContent = MONTH_NAMES[currentMonth - 1] + " " + currentYear;
    }

    var grid = document.getElementById("cal-grid");
    if (grid === null) { return; }

    // Today's date as a string like "2025-03-15"
    // We use this to mark past days and highlight today
    var todayStr = getTodayString();

    // We will build up a big HTML string, then set it all at once
    var html = "";

    // --- Row 1: day-of-week headers ---
    for (var i = 0; i < DAY_LABELS.length; i++) {
        html += '<div class="cal-day-header">' + DAY_LABELS[i] + '</div>';
    }

    // --- Find what day of the week the 1st falls on ---
    // new Date(year, month-1, 1).getDay() returns 0=Sun, 1=Mon ... 6=Sat
    var firstDayOfMonth = new Date(currentYear, currentMonth - 1, 1).getDay();

    // --- Empty cells before the 1st ---
    for (var e = 0; e < firstDayOfMonth; e++) {
        html += '<div class="cal-day cal-day-empty"></div>';
    }

    // --- How many days are in this month? ---
    // new Date(year, month, 0) gives the last day of the previous month,
    // which equals the number of days in the month before it.
    var daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

    // --- Draw a cell for each day ---
    for (var day = 1; day <= daysInMonth; day++) {

        // Build the date string for this cell, e.g. "2025-03-05"
        var dateStr = currentYear
            + "-" + pad2(currentMonth)
            + "-" + pad2(day);

        var isPast    = (dateStr < todayStr);  // string comparison works for YYYY-MM-DD
        var isToday   = (dateStr === todayStr);

        // Pick CSS classes for this cell
        var cellClass = "cal-day";
        if (isPast)  { cellClass += " cal-day-past"; }
        if (isToday) { cellClass += " cal-today"; }

        // Get slot data for this date (from the server response)
        // slotData[dateStr] is an array of mechanic objects, or undefined if no data yet
        var mechanics = slotData[dateStr] || [];

        // Build the slot indicator line
        // Shows: "Ahmed: 3 free  |  Rahim: 0 free  |  ..."
        var slotLine = "";
        if (mechanics.length > 0) {
            var parts = [];
            for (var m = 0; m < mechanics.length; m++) {
                var mech = mechanics[m];
                var freeText = mech.free_slots > 0
                    ? mech.free_slots + " free"
                    : "full";
                parts.push(mech.name.split(" ")[0] + ": " + freeText);
            }
            slotLine = '<div class="cal-slot-line">' + parts.join(" &nbsp;|&nbsp; ") + '</div>';
        }

        // data-date is stored on the cell so the click handler can read it
        html += '<div class="' + cellClass + '" data-date="' + dateStr + '">'
            + '<div class="cal-day-number">' + day + '</div>'
            + slotLine
            + '</div>';
    }

    // Write the complete HTML into the grid in one step
    grid.innerHTML = html;

    // --- Attach click handlers to future day cells ---
    // We do this after setting innerHTML, because the elements now exist in the DOM
    var cells = grid.querySelectorAll(".cal-day:not(.cal-day-past):not(.cal-day-empty)");
    for (var c = 0; c < cells.length; c++) {
        cells[c].addEventListener("click", onCalendarDayClick);
    }
}


// ----------------------------------------------------------
// Step 4: What happens when the user clicks a calendar day.
// ----------------------------------------------------------
function onCalendarDayClick(event) {

    // "this" is the cell that was clicked
    var clickedCell = this;
    var dateStr     = clickedCell.dataset.date; // reads the data-date attribute

    // Highlight this cell, remove highlight from any previously selected cell
    var allCells = document.querySelectorAll(".cal-day");
    for (var i = 0; i < allCells.length; i++) {
        allCells[i].classList.remove("cal-selected");
    }
    clickedCell.classList.add("cal-selected");

    // If the booking form's date input exists on this page, fill it in
    var dateInput = document.getElementById("appointment_date");
    if (dateInput) {
        dateInput.value = dateStr;

        // Also update the mechanic cards to show slots for the clicked date
        var mechanics = slotData[dateStr] || [];
        updateMechanicCards(mechanics);
    }
}


// ----------------------------------------------------------
// Update the mechanic cards on book.php with live slot counts.
// Called when the user clicks a calendar date.
// ----------------------------------------------------------
function updateMechanicCards(mechanics) {
    // Get every mechanic card on the page
    var cards = document.querySelectorAll(".mechanic-card");
    if (cards.length === 0) { return; } // not on the booking page

    for (var i = 0; i < cards.length; i++) {
        var card    = cards[i];
        var cardId  = parseInt(card.dataset.id); // read data-id from the card

        // Find this mechanic's slot data in the array from the server
        var mechanicData = null;
        for (var m = 0; m < mechanics.length; m++) {
            if (mechanics[m].mechanic_id === cardId) {
                mechanicData = mechanics[m];
                break;
            }
        }

        var freeSlots = mechanicData ? mechanicData.free_slots : 4; // default to 4 if no data

        // Find the slot count label inside the card and update it
        var slotLabel = card.querySelector(".mechanic-slots");
        if (slotLabel) {
            if (freeSlots > 0) {
                slotLabel.textContent = freeSlots + (freeSlots === 1 ? " slot free" : " slots free");
                slotLabel.className   = "mechanic-slots slots-available";
            } else {
                slotLabel.textContent = "Fully booked";
                slotLabel.className   = "mechanic-slots slots-full";
            }
        }

        // Mark the card as full (greyed out, not clickable) if no slots remain
        if (freeSlots <= 0) {
            card.classList.add("full");
            card.classList.remove("selected"); // deselect it if it was selected
        } else {
            card.classList.remove("full");
        }
    }
}


/* ============================================================
   PART 2 — BOOKING FORM
   ============================================================ */

function initBookingForm() {

    var form = document.getElementById("booking-form");
    if (form === null) { return; } // not on the booking page

    // Set today as the minimum selectable date in the date picker
    var dateInput = document.getElementById("appointment_date");
    if (dateInput) {
        dateInput.min = getTodayString();
    }

    // Mechanic card click — select a mechanic
    var cards = document.querySelectorAll(".mechanic-card");
    for (var i = 0; i < cards.length; i++) {
        cards[i].addEventListener("click", function () {

            // Ignore clicks on fully-booked cards
            if (this.classList.contains("full")) { return; }

            // Deselect all cards, then select this one
            var allCards = document.querySelectorAll(".mechanic-card");
            for (var j = 0; j < allCards.length; j++) {
                allCards[j].classList.remove("selected");
            }
            this.classList.add("selected");

            // Write the mechanic's ID into the hidden form input
            // so it gets submitted with the form
            var hiddenInput = document.getElementById("mechanic_id");
            if (hiddenInput) {
                hiddenInput.value = this.dataset.id;
            }
        });
    }

    // Basic form check before submit
    form.addEventListener("submit", function (e) {

        var mechId = document.getElementById("mechanic_id");
        var date   = document.getElementById("appointment_date");
        var car    = document.getElementById("car_id");

        var ok = true;

        if (mechId && mechId.value === "") {
            alert("Please select a mechanic.");
            ok = false;
        }
        if (date && date.value === "") {
            alert("Please choose an appointment date.");
            ok = false;
        }
        if (car && car.value === "") {
            alert("Please select a car.");
            ok = false;
        }

        if (!ok) {
            e.preventDefault(); // stop the form from submitting
        }
    });
}


/* ============================================================
   PART 3 — ADMIN EDIT MODAL
   ============================================================
   A "modal" is just a <div> that is normally hidden (display:none)
   and shown by adding a CSS class (display:flex).
   ============================================================ */

function initAdminModal() {

    var modal = document.getElementById("edit-modal");
    if (modal === null) { return; } // not on the admin appointments page

    // Each "Edit" button has data-id, data-date, data-mechanic attributes
    // When clicked, we copy those values into the modal's form fields
    var editButtons = document.querySelectorAll(".btn-edit-appt");
    for (var i = 0; i < editButtons.length; i++) {
        editButtons[i].addEventListener("click", function () {
            document.getElementById("edit-appt-id").value   = this.dataset.id;
            document.getElementById("edit-appt-date").value = this.dataset.date;
            document.getElementById("edit-mechanic").value  = this.dataset.mechanic;
            modal.classList.add("open"); // this CSS class makes it visible
        });
    }

    // Close when the X button is clicked
    var closeBtn = document.getElementById("modal-close");
    if (closeBtn) {
        closeBtn.addEventListener("click", function () {
            modal.classList.remove("open");
        });
    }

    // Close when the dark background behind the modal is clicked
    modal.addEventListener("click", function (e) {
        if (e.target === modal) { // only if the overlay itself was clicked
            modal.classList.remove("open");
        }
    });
}


/* ============================================================
   SMALL UTILITY FUNCTIONS
   ============================================================ */

// Returns today's date as a string: "2025-03-15"
function getTodayString() {
    var d = new Date();
    return d.getFullYear() + "-" + pad2(d.getMonth() + 1) + "-" + pad2(d.getDate());
}

// Pads a number to 2 digits: pad2(3) → "03",  pad2(12) → "12"
function pad2(n) {
    return n < 10 ? "0" + n : "" + n;
}


/* ============================================================
   START HERE — this runs when the page finishes loading
   ============================================================ */
document.addEventListener("DOMContentLoaded", function () {
    initCalendar();      // draw the calendar if one exists on this page
    initBookingForm();   // set up mechanic cards and form check if on book.php
    initAdminModal();    // set up the edit modal if on admin/appointments.php
});
