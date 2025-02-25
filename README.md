# PHP Backend Management Tool / File & Database Manager PHP

## Overview
This PHP script provides a lightweight backend management interface with authentication, file management, and database interaction capabilities. It is intended for local development or testing environments.

## Features
### 1. User Authentication
- Implements a simple login system with hardcoded credentials (`admin/password`).
- Uses PHP sessions to maintain authentication.
- Logout functionality included.

### 2. File Manager
- Browse the entire file system.
- Perform file and directory operations:
  - List directories and files.
  - Read file contents.
  - Create, update, rename, and delete files.
  - Create and delete directories.

### 3. Database Manager
- Connects to a MySQL database using PDO.
- List available databases.
- List tables in a selected database.
- View the structure of a table.
- Execute custom SQL queries.

### 4. Web Interface
- Built with Bootstrap for a responsive UI.
- Navigation menu to switch between file and database management.
- Displays login/logout forms dynamically based on session state.

## Installation
### Requirements
- PHP 7.4+
- MySQL database (optional, for database management features)
- Web server (Apache, Nginx, or built-in PHP server)

### Steps
1. Clone or download the repository.
2. Place the files in your web server's root directory.
3. Start a PHP server:
   ```sh
   php -S localhost:8000
   ```
4. Open `http://localhost:8000` in your browser.
5. Log in using:
   - **Username:** `admin`
   - **Password:** `password`

## Security Considerations
**⚠ WARNING:** This script is not secure for production use without modifications.
- Uses hardcoded credentials (consider database authentication and password hashing).
- Lacks proper access controls (implement role-based access if needed).
- Allows unrestricted file and database operations (consider input validation and permission checks).

## License
This project is open-source under the MIT License.

## Future Enhancements
- Implement user authentication with database-stored credentials.
- Add role-based access control.
- Improve security measures such as file access restrictions.
- Enhance UI with AJAX for a smoother experience.

---
For feedback or contributions, feel free to open an issue or submit a pull request. 🚀

