/*
  Requirement: Populate the single topic page and manage replies.

  NOTE: I added clear TODO labels and explanations so you know exactly
  what each part is supposed to do.
*/

// --- Global Data Store ---
let currentTopicId = null;            // TODO: Will store topic ID from URL
let currentReplies = [];              // TODO: Replies for this specific topic

// --- Element Selections ---
// TODO: Select HTML elements by their IDs from topic.html
const topicSubject = document.getElementById("topic-subject"); // <h1>
const opMessage = document.getElementById("op-message");       // OP message <p>
const opFooter = document.getElementById("op-footer");         // OP footer <footer>
const replyListContainer = document.getElementById("reply-list-container"); // Replies container
const replyForm = document.getElementById("reply-form");       // Reply form
const newReplyText = document.getElementById("new-reply-text"); // Textarea for new reply


/**
 * TODO: Get topic ID from URL (?id=...) .
 */
function getTopicIdFromURL() {
  const params = new URLSearchParams(window.location.search); // Read ?id=...
  return params.get("id"); // return topic ID value
}


/**
 * TODO: Fill the original post section using topic data.
 */
function renderOriginalPost(topic) {
  topicSubject.textContent = topic.subject;        // Set subject
  opMessage.textContent = topic.message;           // Set message
  opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;

  // Optional TODO: Add Delete button for OP
  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete Topic";
  deleteBtn.dataset.id = topic.id;
  deleteBtn.classList.add("delete-topic-btn");
  opFooter.appendChild(deleteBtn);
}


/**
 * TODO: Create <article> element for a single reply.
 */
function createReplyArticle(reply) {
  const article = document.createElement("article");
  article.classList.add("reply");

  const p = document.createElement("p");
  p.textContent = reply.text; // reply text

  const footer = document.createElement("footer");
  footer.textContent = `${reply.author} — ${reply.date}`; // author + date

  const delBtn = document.createElement("button"); // delete button
  delBtn.textContent = "Delete";
  delBtn.classList.add("delete-reply-btn");
  delBtn.dataset.id = reply.id;

  footer.appendChild(delBtn);
  article.appendChild(p);
  article.appendChild(footer);

  return article;
}


/**
 * TODO: Show all replies for this topic.
 */
function renderReplies() {
  replyListContainer.innerHTML = ""; // Clear old replies

  currentReplies.forEach(reply => {
    const replyEl = createReplyArticle(reply);
    replyListContainer.appendChild(replyEl);
  });
}


/**
 * TODO: Handle adding a reply from the form.
 */
function handleAddReply(event) {
  event.preventDefault();

  const text = newReplyText.value.trim(); // get textarea value
  if (!text) return; // ignore empty reply

  const newReply = {
    id: `reply_${Date.now()}`,
    author: "Student", // Hardcoded per requirements
    date: new Date().toISOString().split("T")[0],
    text: text,
  };

  currentReplies.push(newReply);  // Add to memory
  renderReplies();                 // Refresh list
  newReplyText.value = "";        // Clear textarea
}


/**
 * TODO: Handle delete button clicks (reply deletion).
 */
function handleReplyListClick(event) {
  if (!event.target.classList.contains("delete-reply-btn")) return;

  const idToDelete = event.target.dataset.id;
  currentReplies = currentReplies.filter(r => r.id !== idToDelete);
  renderReplies();
}


/**
 * TODO: Load data, display OP and replies, and attach event listeners.
 */
async function initializePage() {
  currentTopicId = getTopicIdFromURL(); // get ID from URL

  if (!currentTopicId) {
    topicSubject.textContent = "Topic not found.";
    return;
  }

  // Fetch both JSON files
  const [topicRes, replyRes] = await Promise.all([
    fetch("topics.json"),
    fetch("replies.json")
  ]);

  const topics = await topicRes.json();
  const replies = await replyRes.json();

  const topic = topics.find(t => t.id === currentTopicId);
  currentReplies = replies[currentTopicId] || []; // load replies for topic

  if (!topic) {
    topicSubject.textContent = "Topic not found.";
    return;
  }

  renderOriginalPost(topic);
  renderReplies();

  // Attach listeners
  replyForm.addEventListener("submit", handleAddReply);
  replyListContainer.addEventListener("click", handleReplyListClick);
}

// --- Initial Page Load ---
initializePage();
