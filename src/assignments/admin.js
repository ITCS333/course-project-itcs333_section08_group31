/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the JSON file.
let resources = [];

// --- Element Selections ---
// TODO: Select the resource form ('#resource-form').
const resourceForm = document.getElementById("resource-form");

// TODO: Select the resources table body ('#resources-tbody').
const resourcesTableBody = document.getElementById("resources-tbody");

// --- Functions ---

/**
 * TODO: Implement the createResourceRow function.
 * It takes one resource object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createResourceRow(resource) {
  // ... your implementation here ...
  const tr = document.createElement("tr");

  // 1. Title cell
  const titleTd = document.createElement("td");
  titleTd.textContent = resource.title;
  tr.appendChild(titleTd);

  // 2. Description cell
  const descTd = document.createElement("td");
  descTd.textContent = resource.description || "";
  tr.appendChild(descTd);

  // 3. Actions cell
  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.type = "button";
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn";
  editBtn.dataset.id = resource.id;
  actionsTd.appendChild(editBtn);

  const deleteBtn = document.createElement("button");
  deleteBtn.type = "button";
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.dataset.id = resource.id;
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `resourcesTableBody`.
 * 2. Loop through the global `resources` array.
 * 3. For each resource, call `createResourceRow()`, and
 * append the resulting <tr> to `resourcesTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  if (!resourcesTableBody) return;

  // 1. Clear current rows
  resourcesTableBody.innerHTML = "";

  // 2 & 3. Loop and append
  resources.forEach((resource) => {
    const tr = createResourceRow(resource);
    resourcesTableBody.appendChild(tr);
  });
}

/**
 * TODO: Implement the handleAddResource function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, and link inputs.
 * 3. Create a new resource object with a unique ID (e.g., `id: `res_${Date.now()}`).
 * 4. Add this new resource object to the global `resources` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
function handleAddResource(event) {
  // ... your implementation here ...
  event.preventDefault();

  if (!resourceForm) return;

  const titleInput = document.getElementById("resource-title");
  const descriptionInput = document.getElementById("resource-description");
  const linkInput = document.getElementById("resource-link");

  const title = (titleInput?.value || "").trim();
  const description = (descriptionInput?.value || "").trim();
  const link = (linkInput?.value || "").trim();

  if (!title || !link) {
    alert("Please provide at least a title and a valid link.");
    return;
  }

  const newResource = {
    id: `res_${Date.now()}`,
    title,
    description,
    link,
  };

  resources.push(newResource);
  renderTable();
  resourceForm.reset();
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `resourcesTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `resources` array by filtering out the resource
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  const target = event.target;

  if (!(target instanceof HTMLElement)) return;

  // Delete handler
  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;
    if (!id) return;

    if (!confirm("Are you sure you want to delete this resource?")) {
      return;
    }

    resources = resources.filter((res) => res.id !== id);
    renderTable();
  }

  // (Optional) Edit logic can be added here later for .edit-btn
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response and store the result in the global `resources` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `resourceForm` (calls `handleAddResource`).
 * 5. Add the 'click' event listener to `resourcesTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  try {
    const response = await fetch("resources.json");

    if (!response.ok) {
      console.error(
        "Failed to load resources.json:",
        response.status,
        response.statusText
      );
      resources = [];
    } else {
      const data = await response.json().catch(() => null);
      if (Array.isArray(data)) {
        resources = data;
      } else {
        resources = [];
      }
    }
  } catch (error) {
    console.error("Error fetching resources.json:", error);
    resources = [];
  }

  // 3. Initial render
  renderTable();

  // 4. Attach form listener
  if (resourceForm) {
    resourceForm.addEventListener("submit", handleAddResource);
  }

  // 5. Attach table click listener
  if (resourcesTableBody) {
    resourcesTableBody.addEventListener("click", handleTableClick);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();