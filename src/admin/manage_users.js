//Requirement: Add interactivity and data management to the Admin Portal.
// Instructions:
// 1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
// Example: <script src="manage_users.js" defer></script>
// 2. Implement the JavaScript functionality as described in the TODO comments.
// 3. All data management will be done by manipulating the 'students' array
// and re-rendering the table.*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

// NEW: Base URL for the PHP Student Management API
const STUDENT_API_URL = "IndexStudent_Management.php";

// NEW: Endpoint for changing the ADMIN password
const ADMIN_PASSWORD_API_URL = "Admin_change_password.php";

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the student table body (tbody).
const studentTableBody = document.getElementById("student-table-body");
// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
const addStudentForm = document.getElementById("add-student-form");
// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
const changePasswordForm = document.getElementById("password-form");
// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
const searchInput = document.getElementById("search-input");
// TODO: Select all table header (th) elements in thead.
const tableHeaders = document.querySelectorAll("#student-table thead th");
// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.*/
function createStudentRow(student) {
  // ... your implementation here ...
  // * This function should take a student object {name, id, email} and return a <tr> element.
  // * The <tr> should contain:
  const tr = document.createElement("tr");
  //* 1. A <td> for the student's name.
  const nameTd = document.createElement("td");
  nameTd.textContent = student.name;
  tr.appendChild(nameTd);
  // * 2. A <td> for the student's ID.
  const idTd = document.createElement("td");
  idTd.textContent = student.id;
  tr.appendChild(idTd);
  // * 3. A <td> for the student's email.
  const emailTd = document.createElement("td");
  emailTd.textContent = student.email;
  tr.appendChild(emailTd);
  // * 4. A <td> containing two buttons:
  const actionTd = document.createElement("td");
  // * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
  const editBtn = document.createElement("button");
  editBtn.type = "button";
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn";
  editBtn.dataset.id = student.id;

  actionTd.appendChild(editBtn);
  // * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
  const deleteBtn = document.createElement("button");
  deleteBtn.type = "button";
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.dataset.id = student.id;

  actionTd.appendChild(deleteBtn);

  tr.appendChild(actionTd);
  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:*/
function renderTable(studentArray) {
  // ... your implementation here ...
  // * 1. Clear the current content of the `studentTableBody`.
  if (!studentTableBody) return;
  studentTableBody.innerHTML = "";
  // * 2. Loop through the provided array of students.
  studentArray.forEach((student) => {
    // * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
    const tr = createStudentRow(student);
    studentTableBody.appendChild(tr);
  });
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:*/
async function handleChangePassword(event) {
  // ... your implementation here ...
  // * 1. Prevent the form's default submission behavior.
  event.preventDefault();

  // * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
  const currentPasswordInput = document.getElementById("current-password");
  const newPasswordInput = document.getElementById("new-password");
  const confirmPasswordInput = document.getElementById("confirm-password");

  const currentPassword = currentPasswordInput.value;
  const newPassword = newPasswordInput.value;
  const confirmPassword = confirmPasswordInput.value;

  // * 3. Perform validation:
  if (newPassword !== confirmPassword) {
    //    * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
    alert("Passwords do not match.");
    return;
  }
  //    * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
  if (newPassword.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  try {
    // Send the password change request to the PHP API
    const response = await fetch(ADMIN_PASSWORD_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        current_password: currentPassword,
        new_password: newPassword,
      }),
    });

    const result = await response.json().catch(() => null);

    if (!response.ok || !result || result.success === false) {
      alert((result && result.message) || "Failed to update password.");
      return;
    }

    // * 4. If validation passes, show an alert: "Password updated successfully!"
    alert(result.message || "Password updated successfully!");

    // * 5. Clear all three password input fields.
    currentPasswordInput.value = "";
    newPasswordInput.value = "";
    confirmPasswordInput.value = "";
  } catch (error) {
    console.error("Error updating password via API:", error);
    alert("An error occurred while updating the password.");
  }
}


/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:*/
async function handleAddStudent(event) {
  // ... your implementation here ...
  // * 1. Prevent the form's default submission behavior.
  event.preventDefault();
  // * 2. Get the values from "student-name", "student-id", and "student-email".
  const StudentnameInput = document.getElementById("student-name");
  const StudentidInput = document.getElementById("student-id");
  const StudentemailInput = document.getElementById("student-email");
  const defaultPasswordInput = document.getElementById("default-password");

  const name = StudentnameInput.value.trim();
  const id = StudentidInput.value.trim();
  const email = StudentemailInput.value.trim();
  const defaultPassword = (defaultPasswordInput.value || "").trim() || "password123";

  // * 3. Perform validation:
  if (!name || !id || !email) {
    // * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
    alert("Please fill out all required fields.");
    return;
  }
  // * - (Optional) Check if a student with the same ID already exists in the 'students' array.
  const existingStudent = students.some((student) => student.id === id);
  if (existingStudent) {
    // * - If a student with the same ID exists, show an alert: "A student with this ID already exists."
    alert("A student with this ID already exists.");
    return;
  }

  // * 4. If validation passes:
  // * - Create a new student object: { name, id, email }.
  const newStudent = { name, id, email };

  // NEW: Send the new student to the PHP API
  try {
    const response = await fetch(STUDENT_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        student_id: id,
        name,
        email,
        password: defaultPassword,
      }),
    });

    const result = await response.json().catch(() => null);

    if (!response.ok || !result || result.success === false) {
      alert((result && result.message) || "Failed to add student via API.");
      return;
    }

    alert("Student added successfully.");
    // Reload students from API
    await refreshStudentsFromAPI();
  } catch (error) {
    console.error("Error adding student via API:", error);
    alert("An error occurred while adding the student.");
    return;
  }

  // * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
  StudentnameInput.value = "";
  StudentidInput.value = "";
  StudentemailInput.value = "";
  defaultPasswordInput.value = "";
}

/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:*/
async function handleTableClick(event) {
  // ... your implementation here ...
  // * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
  const target = event.target;
  // * 2. If it is a "delete-btn":
  if (target.classList.contains("delete-btn")) {
    // * - Get the `data-id` attribute from the button.
    const studentId = target.dataset.id;
    if (!studentId) return;

    if (!confirm("Are you sure you want to delete this student?")) {
      return;
    }

    try {
      const response = await fetch(
        `${STUDENT_API_URL}?student_id=${encodeURIComponent(studentId)}`,
        {
          method: "DELETE",
        }
      );

      const result = await response.json().catch(() => null);

      if (!response.ok || !result || result.success === false) {
        alert((result && result.message) || "Failed to delete student.");
        return;
      }

      // Reload students from API
      await refreshStudentsFromAPI();
    } catch (error) {
      console.error("Error deleting student via API:", error);
      alert("An error occurred while deleting the student.");
    }
    return;
  }

  // * 3. (Optional) Check for "edit-btn" and implement edit logic.
  if (target.classList.contains("edit-btn")) {
    const studentId = target.dataset.id;
    if (!studentId) return;

    const student = students.find((s) => s.id === studentId);
    if (!student) return;

    const newName = prompt("Edit name:", student.name);
    const newEmail = prompt("Edit email:", student.email);

    if (!newName || !newEmail) {
      return;
    }

    // Update on server via API
    try {
      const response = await fetch(STUDENT_API_URL, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          student_id: studentId,
          name: newName,
          email: newEmail,
        }),
      });

      const result = await response.json().catch(() => null);

      if (!response.ok || !result || result.success === false) {
        alert((result && result.message) || "Failed to update student.");
        return;
      }

      // Reload from API
      await refreshStudentsFromAPI();
    } catch (error) {
      console.error("Error updating student via API:", error);
      alert("An error occurred while updating the student.");
    }
  }
}

/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:*/
function handleSearch(event) {
  // ... your implementation here ...
  // * 1. Get the search term from `searchInput.value` and convert it to lowercase.
  const searchTerm = searchInput.value.toLowerCase().trim();
  // * 2. If the search term is empty, call `renderTable(students)` to show all students.
  if (!searchTerm) {
    renderTable(students);
    return;
  }
  // * 3. If the search term is not empty:
  // * - Filter the global 'students' array to find students whose name (lowercase)
  // * includes the search term.
  const filtered = students.filter((student) =>
    student.name.toLowerCase().includes(searchTerm)
  );
  // * - Call `renderTable` with the *filtered array*.
  renderTable(filtered);
}

/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:*/
function handleSort(event) {
  // ... your implementation here ...
  // * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
  const th = event.currentTarget;
  const columnindex = th.cellIndex;
  // * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
  let sortBy;
  if (columnindex === 0) {
    sortBy = "name";
  } else if (columnindex === 1) {
    sortBy = "id";
  } else if (columnindex === 2) {
    sortBy = "email";
  } else {
    return;
  }
  // * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
  // * to track the current direction. Toggle between "asc" and "desc".
  let sortDir = th.dataset.sortDir || "asc";
  sortDir = sortDir === "asc" ? "desc" : "asc";
  th.dataset.sortDir = sortDir;
  // * 4. Sort the global 'students' array *in place* using `array.sort()`.
  // * - For 'name' and 'email', use `localeCompare` for string comparison.
  // * - For 'id', compare the values as numbers.
  students.sort((a, b) => {
    let valA = a[sortBy];
    let valB = b[sortBy];

    if (sortBy === "id") {
      const numA = Number(valA);
      const numB = Number(valB);
      if (numA < numB) return sortDir === "asc" ? -1 : 1;
      if (numA > numB) return sortDir === "asc" ? 1 : -1;
      return 0;
    } else {
      const cmp = String(valA).localeCompare(String(valB));
      return sortDir === "asc" ? cmp : -cmp;
    }
  });
  // * 5. Respect the sort direction (ascending or descending).
  // * 6. After sorting, call `renderTable(students)` to update the view.
  renderTable(students);
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:*/
async function loadStudentsAndInitialize() {
  // ... your implementation here ...
  // * 1. Use the `fetch()` API to get data from 'students.json'.
  await refreshStudentsFromAPI();
  // * 5. Call `renderTable(students)` to populate the table for the first time.
  // (done inside refreshStudentsFromAPI)

  // * 6. After data is loaded, set up all the event listeners:
  // * - "submit" on `changePasswordForm` -> `handleChangePassword`
  if (changePasswordForm) {
    changePasswordForm.addEventListener("submit", handleChangePassword);
  }
  // * - "submit" on `addStudentForm` -> `handleAddStudent`
  if (addStudentForm) {
    addStudentForm.addEventListener("submit", handleAddStudent);
  }
  // * - "click" on `studentTableBody` -> `handleTableClick`
  if (studentTableBody) {
    studentTableBody.addEventListener("click", handleTableClick);
  }
  // * - "input" on `searchInput` -> `handleSearch`
  if (searchInput) {
    searchInput.addEventListener("input", handleSearch);
  }
  // * - "click" on each header in `tableHeaders` -> `handleSort`
  tableHeaders.forEach((th) => {
    th.addEventListener("click", handleSort);
  });
}

// NEW: helper to fetch students from the PHP API and render them
async function refreshStudentsFromAPI() {
  try {
    const response = await fetch(STUDENT_API_URL);
    // * 2. Check if the response is 'ok'. If not, log an error.
    if (!response.ok) {
      console.error(
        "Failed to fetch students from API:",
        response.status,
        response.statusText
      );
      students = [];
    } else {
      // * 3. Parse the JSON response (e.g., `await response.json()`).
      const result = await response.json();
      // * 4. Assign the resulting array to the global 'students' variable.
      if (result && result.success && Array.isArray(result.data)) {
        // Map API fields -> front-end fields
        students = result.data.map((s) => ({
          id: s.student_id,
          name: s.name,
          email: s.email,
        }));
      } else {
        console.error("Unexpected API response format:", result);
        students = [];
      }
    }
  } catch (error) {
    console.error("Error fetching students from API:", error);
    students = [];
  }
  // * 5. Call `renderTable(students)` to populate the table for the first time.
  renderTable(students);
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();