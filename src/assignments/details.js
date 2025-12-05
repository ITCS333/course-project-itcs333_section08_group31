/*
  Requirement: Populate the assignment detail page and discussion forum.
  Structure matches src/resources/details.js
*/

// --- Global Data Store ---
let currentAssignmentId = null;
let currentComments = [];
const API_URL = "api/indexCourse_Resources.php";

// --- Element Selections ---
const assignmentTitle = document.getElementById("asg-title");
const assignmentDescription = document.getElementById("asg-desc");
const assignmentDueDate = document.getElementById("asg-due");
const assignmentFilesList = document.getElementById("asg-files");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newComment = document.getElementById("new-comment");

// --- Functions ---

/**
 * Helper to get ID from URL.
 * Matches getResourceIdFromURL in resources/details.js
 */
function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

/**
 * Renders the assignment details.
 * Matches renderResourceDetails, but handles Due Date and File List.
 */
function renderAssignmentDetails(assignment) {
  if (!assignment) return;

  if (assignmentTitle) {
    assignmentTitle.textContent = assignment.title || "Assignment Details";
  }
  if (assignmentDescription) {
    assignmentDescription.textContent = assignment.description || "";
  }
  if (assignmentDueDate) {
    assignmentDueDate.textContent = assignment.due_date || "No Date";
  }

  // Handle Files List (Specific to Assignments)
  if (assignmentFilesList) {
    assignmentFilesList.innerHTML = ""; // Clear existing
    if (Array.isArray(assignment.files) && assignment.files.length > 0) {
      assignment.files.forEach(fileName => {
        const li = document.createElement("li");
        const a = document.createElement("a");
        a.href = "#"; // Placeholder, or path to file download
        a.textContent = fileName;
        li.appendChild(a);
        assignmentFilesList.appendChild(li);
      });
    } else {
        const li = document.createElement("li");
        li.textContent = "No files attached.";
        assignmentFilesList.appendChild(li);
    }
  }
}

/**
 * Creates HTML for a single comment.
 * Matches createCommentArticle in resources/details.js
 */
function createCommentArticle(comment) {
  const article = document.createElement("article");

  const p = document.createElement("p");
  p.textContent = comment.text || "";
  article.appendChild(p);

  const footer = document.createElement("footer");
  footer.textContent = `Posted by: ${comment.author || "Student"} on ${comment.created_at || "Recent"}`;
  article.appendChild(footer);

  return article;
}

/**
 * Renders the list of comments.
 * Matches renderComments in resources/details.js
 */
function renderComments() {
  if (!commentList) return;

  commentList.innerHTML = "";

  currentComments.forEach((comment) => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

/**
 * Handles posting a new comment.
 * Matches handleAddComment in resources/details.js, but uses API.
 */
async function handleAddComment(event) {
  event.preventDefault();

  if (!newComment) return;

  const text = newComment.value.trim();
  if (!text) return;

  try {
    const response = await fetch(`${API_URL}?action=comment`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        assignment_id: currentAssignmentId,
        author: "Student", // In a real app, this comes from the session
        text: text
      })
    });

    const result = await response.json();

    if (result.success) {
      // Reload comments to show the new one with server timestamp
      await loadCommentsOnly(); 
      newComment.value = "";
    } else {
      alert("Failed to post comment");
    }
  } catch (error) {
    console.error("Error posting comment:", error);
  }
}

/**
 * Helper to fetch just comments (used after posting).
 */
async function loadCommentsOnly() {
    if (!currentAssignmentId) return;
    try {
        const res = await fetch(`${API_URL}?action=comments&assignment_id=${currentAssignmentId}`);
        const result = await res.json();
        if (result.success && Array.isArray(result.data)) {
            currentComments = result.data;
            renderComments();
        }
    } catch(e) { console.error(e); }
}

/**
 * Main initialization function.
 * Matches initializePage in resources/details.js
 */
async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();

  if (!currentAssignmentId) {
    if (assignmentTitle) assignmentTitle.textContent = "Assignment not found.";
    return;
  }

  try {
    // Fetch Assignment Details
    const asgRes = await fetch(`${API_URL}?id=${currentAssignmentId}`);
    const asgResult = await asgRes.json();

    // Fetch Comments
    const commentsRes = await fetch(`${API_URL}?action=comments&assignment_id=${currentAssignmentId}`);
    const commentsResult = await commentsRes.json();

    if (asgResult.success) {
        renderAssignmentDetails(asgResult.data);
    } else {
        if (assignmentTitle) assignmentTitle.textContent = "Assignment not found.";
    }

    if (commentsResult.success && Array.isArray(commentsResult.data)) {
        currentComments = commentsResult.data;
        renderComments();
    }

    if (commentForm) {
      commentForm.addEventListener("submit", handleAddComment);
    }

  } catch (error) {
    console.error("Error initializing page:", error);
    if (assignmentTitle) assignmentTitle.textContent = "Error loading data.";
  }
}

// --- Initial Page Load ---
initializePage();
