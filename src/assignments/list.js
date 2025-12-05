/*
  Requirement: Populate the "Course Assignments" list page.
  Structure matches src/resources/list.js
*/

// --- Element Selections ---
const listSection = document.getElementById("assignment-list");
const API_URL = "api/indexCourse_Resources.php";

// --- Functions ---

/**
 * Creates an article element for a single assignment.
 * Matches structure of createResourceArticle in resources/list.js
 */
function createAssignmentArticle(assignment) {
  const article = document.createElement("article");

  // Title
  const heading = document.createElement("h2");
  heading.textContent = assignment.title;
  article.appendChild(heading);

  // Due Date (Specific to Assignments)
  const duePara = document.createElement("p");
  duePara.innerHTML = `<strong>Due Date:</strong> ${assignment.due_date}`;
  article.appendChild(duePara);

  // Description
  const descPara = document.createElement("p");
  // Truncate description for the list view if it's too long
  const descText = assignment.description || "";
  descPara.textContent = descText.length > 100 ? descText.substring(0, 100) + "..." : descText;
  article.appendChild(descPara);

  // Link
  const link = document.createElement("a");
  link.href = `details.html?id=${assignment.id}`;
  link.textContent = "View Details & Submit";
  article.appendChild(link);

  return article;
}

/**
 * Fetches assignments from the API and populates the list.
 * Matches structure of loadResources in resources/list.js
 */
async function loadAssignments() {
  if (!listSection) return;

  try {
    const response = await fetch(API_URL);
    const result = await response.json();

    // Clear existing content
    listSection.innerHTML = "";

    if (result.success && Array.isArray(result.data)) {
        // Loop and append
        result.data.forEach((asg) => {
            const article = createAssignmentArticle(asg);
            listSection.appendChild(article);
        });
        
        if (result.data.length === 0) {
            listSection.innerHTML = "<p>No assignments available.</p>";
        }
    } else {
        console.error("Failed to load assignments or invalid data format.");
        listSection.innerHTML = "<p>Error loading assignments.</p>";
    }

  } catch (error) {
    console.error("Error fetching assignments:", error);
    listSection.innerHTML = "<p>Error loading assignments.</p>";
  }
}

// --- Initial Page Load ---
loadAssignments();
