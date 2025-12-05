/*
  Requirement: Make the "Manage Assignments" page interactive.
  Structure matches src/resources/admin.js but connected to the PHP API.
*/

// --- Global Data Store ---
let assignments = [];
const API_URL = "api/indexCourse_Resources.php";

// --- Element Selections ---
const assignmentForm = document.getElementById("assignment-form");
const assignmentsTableBody = document.getElementById("assignments-tbody");
const cancelBtn = document.getElementById("cancel-btn");
const hiddenIdInput = document.getElementById("asg-id");

// --- Functions ---

/**
 * Creates a table row for an assignment.
 * Matches structure of createResourceRow in resources/admin.js
 */
function createAssignmentRow(assignment) {
  const tr = document.createElement("tr");

  // 1. Title cell
  const titleTd = document.createElement("td");
  titleTd.textContent = assignment.title;
  tr.appendChild(titleTd);

  // 2. Due Date cell
  const dateTd = document.createElement("td");
  dateTd.textContent = assignment.due_date;
  tr.appendChild(dateTd);

  // 3. Actions cell
  const actionsTd = document.createElement("td");

  // Edit Button
  const editBtn = document.createElement("button");
  editBtn.type = "button";
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn";
  editBtn.dataset.id = assignment.id;
  actionsTd.appendChild(editBtn);

  // Delete Button
  const deleteBtn = document.createElement("button");
  deleteBtn.type = "button";
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.dataset.id = assignment.id; // Use real database ID
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

/**
 * Renders the table using the global assignments array.
 * Matches renderTable in resources/admin.js
 */
function renderTable() {
  if (!assignmentsTableBody) return;

  assignmentsTableBody.innerHTML = "";

  assignments.forEach((asg) => {
    const tr = createAssignmentRow(asg);
    assignmentsTableBody.appendChild(tr);
  });
}

/**
 * Handles Form Submission (Create or Update).
 * Matches handleAddResource in resources/admin.js, but uses API.
 */
async function handleSaveAssignment(event) {
  event.preventDefault();

  const id = hiddenIdInput.value;
  const title = document.getElementById("asg-title").value.trim();
  const description = document.getElementById("asg-desc").value.trim();
  const dueDate = document.getElementById("asg-due").value;
  const filesText = document.getElementById("asg-files").value.trim();
  
  // Convert files text to array (split by new lines)
  const filesArray = filesText ? filesText.split('\n').map(f => f.trim()).filter(f => f) : [];

  if (!title || !dueDate) {
    alert("Please provide a title and due date.");
    return;
  }

  const payload = {
    id: id, // will be empty string if creating new
    title: title,
    description: description,
    due_date: dueDate,
    files: filesArray
  };

  try {
    const method = id ? "PUT" : "POST";
    const response = await fetch(API_URL, {
      method: method,
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const result = await response.json();

    if (result.success) {
        // Reset form and reload data
        resetForm();
        loadAndInitialize(); 
    } else {
        alert("Error: " + result.message);
    }
  } catch (error) {
    console.error("Error saving assignment:", error);
  }
}

/**
 * Helper to reset form state
 */
function resetForm() {
    assignmentForm.reset();
    hiddenIdInput.value = "";
    cancelBtn.style.display = "none";
}

/**
 * Handles clicks on the table (Event Delegation).
 * Matches handleTableClick in resources/admin.js
 */
async function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  const id = target.dataset.id;
  if (!id) return;

  // DELETE ACTION
  if (target.classList.contains("delete-btn")) {
    if (!confirm("Are you sure you want to delete this assignment?")) return;

    try {
        const response = await fetch(API_URL, {
            method: "DELETE",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id })
        });
        
        await loadAndInitialize(); // Refresh list
    } catch (error) {
        console.error("Error deleting assignment:", error);
    }
  }

  // EDIT ACTION
  if (target.classList.contains("edit-btn")) {
    const assignment = assignments.find(a => a.id == id);
    if (assignment) {
        hiddenIdInput.value = assignment.id;
        document.getElementById("asg-title").value = assignment.title;
        document.getElementById("asg-desc").value = assignment.description;
        document.getElementById("asg-due").value = assignment.due_date;
        
        // Convert files array back to text for editing
        if (assignment.files && Array.isArray(assignment.files)) {
            document.getElementById("asg-files").value = assignment.files.join('\n');
        } else {
            document.getElementById("asg-files").value = "";
        }

        cancelBtn.style.display = "inline-block";
    }
  }
}

/**
 * Loads data from API and initializes listeners.
 * Matches loadAndInitialize in resources/admin.js
 */
async function loadAndInitialize() {
  try {
    const response = await fetch(API_URL);
    const result = await response.json();

    if (result.success && Array.isArray(result.data)) {
      assignments = result.data;
    } else {
      assignments = [];
    }
    
    renderTable();

  } catch (error) {
    console.error("Error fetching assignments:", error);
    assignments = [];
    renderTable();
  }
}

// --- Event Listeners ---
// Initialize only if elements exist to prevent errors
if (assignmentForm) {
    assignmentForm.addEventListener("submit", handleSaveAssignment);
}

if (assignmentsTableBody) {
    assignmentsTableBody.addEventListener("click", handleTableClick);
}

if (cancelBtn) {
    cancelBtn.addEventListener("click", resetForm);
}

// --- Initial Page Load ---
loadAndInitialize();
