<?php
session_start();

//If the user is not logged in, send them back to the login page
if(empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true){
    header('Location: login.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- TODO: Add the 'meta' tag for character encoding (UTF-8). -->
     <meta charset="UTF-8">
    <!-- TODO: Add the responsive 'viewport' meta tag. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- TODO: Add a 'title' for the page, e.g., "Admin Portal". -->
    <title>
        Admin Portal
    </title>
    <!-- TODO: Link to a CSS file or a CSS framework. -->
        <link rel="stylesheet" href="styles.css">
</head>
<body>

    <!-- TODO: Create a 'header' element for the top of the page. -->
     <header class="admin-header">
        <!-- TODO: Inside the header, add a main heading (e.g., 'h1') with the text "Admin Portal". -->
         <div class="header-left">
         <h1>
            Admin Portal
         </h1>
        </div>

    <!--Small header area on the right for the Logout button -->
        <div class= "header-right">

             <!-- Logout goes to logout.php and uses POST for the better security -->
              <form action= "logout.php" method="post">
                <button type="submit" id="logout-button">
                    Logout
                </button>
            </form>
        </div> 
    <!-- End of the header. -->
     </header>
    <!-- TODO: Create a 'main' element to hold the primary content of the portal. -->
     <main>
        <!-- Section 1: Password Management -->
        <!-- TODO: Create a 'section' for the password management functionality. -->
         <section>
            <!-- TODO: Add a sub-heading (e.g., 'h2') with the text "Change Your Password". -->
                <h2>
                    Change Your Password
                </h2>
            <!-- TODO: Create a 'form' for changing the password. The 'action' can be '#'. -->
                <form action="#" id="password-form" method="post">
                <!-- TODO: Use a 'fieldset' to group the password-related fields. -->
                    <fieldset>
                    <!-- TODO: Add a 'legend' for the fieldset, e.g., "Password Update". -->
                     <legend>
                        Password Update
                     </legend>
                    <!-- TODO: Add a 'label' for the current password input. 'for' should be "current-password". -->
                     <label for="current-password">
                        Current Password:
                     </label>
                    <!-- TODO: Add an 'input' for the current password.
                         - type="password"
                         - id="current-password"
                         - required -->
                        <input type="password" id="current-password" required>
                    <!-- TODO: Add a 'label' for the new password input. 'for' should be "new-password". -->
                    <label for="new-password">
                        New Password:
                    </label>
                    <!-- TODO: Add an 'input' for the new password.
                         - type="password"
                         - id="new-password"
                         - minlength="8"
                         - required -->
                    <input type="password" id="new-password" minlength="8" required>
                    <!-- TODO: Add a 'label' for the confirm password input. 'for' should be "confirm-password". -->
                    <label for="confirm-password">
                        Confirm New Password:
                    </label>
                    <!-- TODO: Add an 'input' to confirm the new password.
                         - type="password"
                         - id="confirm-password"
                         - required -->
                    <input type="password" id="confirm-password" required>
                    <!-- TODO: Add a 'button' to submit the form.
                         - type="submit"
                         - id="change"
                         - Text: "Update Password" -->
                    <button type="submit" id="change">
                        Update Password
                    </button>
                <!-- End of the fieldset. -->
                 </fieldset>
            <!-- End of the password form. -->
            </form>
        <!-- End of the password management section. -->
         </section>


        <!-- Section 2: Student Management -->
        <!-- TODO: Create another 'section' for the student management functionality. -->
         <section>
            <!-- TODO: Add a sub-heading (e.g., 'h2') with the text "Manage Students". -->
                <h2>
                    Manage Students
                </h2>
            <!-- Subsection 2.1: Add New Student Form -->
            <!-- TODO: Create a 'details' element so the "Add Student" form can be collapsed. -->
                <details>
                <!-- TODO: Add a 'summary' element inside 'details' with the text "Add New Student". -->
                    <summary>
                        Add New Student
                    </summary>
                <!-- TODO: Create a 'form' for adding a new student. 'action' can be '#'. -->
                    <form action="#" id="add-student-form" method="post">
                    <!-- TODO: Use a 'fieldset' to group the new student fields. -->
                        <fieldset>
                        <!-- TODO: Add a 'legend' for the fieldset, e.g., "New Student Information". -->
                            <legend>
                                New Student Information
                            </legend>
                        <!-- TODO: Add a 'label' and 'input' for the student's full name.
                             - label 'for': "student-name"
                             - input 'id': "student-name"
                             - input 'type': "text"
                             - input: required -->
                             <label for="student-name">
                                Student Name:
                                </label>
                                <input type="text" id="student-name" required>
                        <!-- TODO: Add a 'label' and 'input' for the student's ID.
                             - label 'for': "student-id"
                             - input 'id': "student-id"
                             - input 'type': "text"
                             - input: required -->
                             <label for="student-id">
                                Student ID:
                                </label>
                                <input type="text" id="student-id" required>
                        <!-- TODO: Add a 'label' and 'input' for the student's email.
                             - label 'for': "student-email"
                             - input 'id': "student-email"
                             - input 'type': "email"
                             - input: required -->
                                <label for="student-email">
                                    Student Email:
                                    </label>
                                    <input type="email" id="student-email" required>
                        <!-- TODO: Add a 'label' and 'input' for the default password.
                             - label 'for': "default-password"
                             - input 'id': "default-password"
                             - input 'type': "text"
                             - You can pre-fill this with a value like "password123" or leave it blank. -->
                                <label for="default-password">
                                    Default Password:
                                    </label>
                                    <input type="text" id="default-password" value="">
                        <!-- TODO: Add a 'button' to submit the form.
                             - type="submit"
                             - id="add"
                             - Text: "Add Student" -->
                            <button type="submit" id="add">
                                Add Student
                            </button>
                    <!-- End of the fieldset. -->
                        </fieldset>
                <!-- End of the add student form. -->
                    </form>
            <!-- End of the 'details' element. -->
             </details>

            <!-- Subsection 2.2: Student List -->
            <!-- Search Label -->
            <label for="search-input">
                Search Students:
            </label>
            <!-- Search Input -->
            <input type="text" id="search-input" placeholder="Search student name"> 
            <!-- TODO: Add a sub-heading (e.g., 'h3') for the list of students, "Registered Students". -->
                <h3>
                    Registered Students
                </h3>
            <!-- TODO: Create a 'table' to display the list of students. Give it an id="student-table". -->
            <table id="student-table">
                <!-- TODO: Create a 'thead' for the table headers. -->
                <thead>
                    <!-- TODO: Create a 'tr' (table row) inside the 'thead'. -->
                    <tr>
                        <!-- TODO: Create 'th' (table header) cells for "Name", "Student ID", "Email", and "Actions". -->
                        <th> Name </th>
                        <th> Student ID </th>
                        <th> Email </th>
                        <th> Actions </th>
                    <!-- End of the row. -->
                    </tr>
                <!-- End of 'thead'. -->
                </thead>
                <!-- TODO: Create a 'tbody' for the table body, where student data will go. -->
                <tbody id="student-table-body">
                    <!-- TODO: For now, add 2-3 rows of dummy data so you can see how the table is structured. -->
                    <!-- Example Student Row: -->
                    <!-- TODO: Create a 'tr' for a student record. -->
                    <tr>
                        <!-- TODO: Create 'td' (table data) cells for a student's name (e.g., "John Doe"), ID (e.g., "12345"), and email (e.g., "john.doe@example.com"). -->
                        <td> John Doe </td>
                        <td> 12345 </td>
                        <td> John_doe@example.com </td>
                        <!-- TODO: Create a final 'td' for action buttons. -->
                        <td>
                            <!-- TODO: Add an "Edit" button. -->
                            <button type="button">
                                Edit
                            </button>
                            <!-- TODO: Add a "Delete" button. -->
                            <button type="button">
                                Delete
                            </button>
                        <!-- End of the actions 'td'. -->
                        </td>
                    <!-- End of the student row. -->
                    </tr>
                    <!-- TODO: Add another 'tr' for a second student as another example. -->
                    <tr>
                        <td> Jane Doe </td>
                        <td> 67890 </td>
                        <td> Jane_Doe@example.com </td>
                        <td>
                            <button type="button">
                                Edit
                            </button>
                            <button type="button">
                                Delete
                            </button>
                        </td>
                    </tr>
                <!-- End of 'tbody'. -->
                </tbody>
            <!-- End of the table. -->
            </table>
        <!-- End of the student management section. -->
            </section>
    <!-- End of the main content area. -->
     </main>
     <!--1. Link this file to your HTML using a <script> tag with the 'defer' attribute.-->
     <script src="manage_users.js" defer></script>
</body>
</html>