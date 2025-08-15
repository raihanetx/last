# Website with PHP Backend and Admin Dashboard

This project has been updated with a complete PHP backend, a JSON file-based data store, and a fully functional admin dashboard to manage the website's content.

## File Structure

Here are the new files and directories that have been added:

-   `data.json`: This file is the "database" for your website. It stores all the information for product categories and the products themselves in JSON format.
-   `index.php`: The main public-facing file of your website (previously `index.html`). It now dynamically loads content from `data.json`.
-   `/api/`: This directory contains the PHP scripts that act as the backend API.
    -   `get_data.php`: (Not used directly, but for reference) A simple script to view all data.
    -   `admin_api.php`: The core of the admin backend. It handles all logic for creating, updating, and deleting content.
-   `/admin/`: This directory contains the administration panel.
    -   `index.php`: The login page and main dashboard interface for managing your site.

## Setup Instructions

To get the website and admin panel running on your server, follow these two critical steps.

### 1. Set File Permissions

For the admin panel to save your changes, the web server needs to have permission to write to the `data.json` file.

Connect to your server via SSH or a terminal and run the following commands from the root directory of your website:

**Option A: Change Ownership (Recommended)**
This is the most secure method. It makes the web server user (commonly `www-data` or `apache`) the owner of the file.

```bash
# Find your web server's user if you are unsure. It's often www-data.
# ps aux | egrep '(apache|httpd|nginx)'

# Replace 'www-data' with your server's user if different.
sudo chown www-data:www-data data.json
sudo chmod 664 data.json
```

**Option B: Change Permissions (Easier, but less secure)**
If you are unsure about user ownership, you can make the file world-writable. This is less secure but works on most hosting environments.

```bash
chmod 666 data.json
```

### 2. Change the Admin Password

A default password has been set for the admin dashboard. You **must change this immediately** for security.

1.  Open the file `admin/index.php`.
2.  On line 5, you will see the following:
    ```php
    $admin_password = 'password123'; // CHANGE THIS!
    ```
3.  Replace `'password123'` with a strong, unique password of your choice.
4.  Save the file.

## How to Use the Admin Dashboard

1.  **Access the Dashboard:**
    Navigate to `https://yourwebsite.com/admin/` in your web browser.

2.  **Login:**
    Enter the new password you set in the previous step.

3.  **Managing Categories:**
    -   **Add:** Fill in the "Category Name" and "Font Awesome Icon" fields and click "Add Category". You can find Font Awesome icon classes on their [website](https://fontawesome.com/icons).
    -   **Delete:** Click the "Delete" button next to a category. You can only delete a category if it has no products assigned to it.

4.  **Managing Products:**
    -   **Add:** Click the "Add New Product" button. A modal window will appear. Fill in the product details and click "Save".
    -   **Edit:** Click the "Edit" button next to any product. The same modal will appear, pre-filled with the product's current information. Make your changes and click "Save".
    -   **Delete:** Click the "Delete" button next to any product.

All changes made in the admin dashboard will be saved to `data.json` and will appear on your public website instantly.
