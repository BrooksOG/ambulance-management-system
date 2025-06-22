# Ambulance management system

## Overview
The Ambulance Management System is a web-based application developed for Hospitals to streamline emergency medical services. It facilitates real-time incident reporting, ambulance assignment, status tracking, inventory management, and automated dispatch processes. Built as a school project, the system prioritizes functionality for public users, dispatchers, paramedics, and admins, ensuring efficient emergency response without advanced security features.

## Features
- **Incident Reporting**: Public users can submit emergency incidents with details like location and description.
- **Ambulance Assignment**: Dispatchers assign available ambulances to incidents, ensuring no double assignments.
- **Status Tracking**: Real-time updates on incident and ambulance statuses (e.g., AVAILABLE, DISPATCHED, CLOSED).
- **Inventory Management**: Paramedics update ambulance inventory post-mission.
- **Patient Outcome Recording**: Paramedics log patient names and outcome comments upon incident closure.
- **Reporting**: Admins and dispatchers access patient outcome reports; paramedics view their own reports.
- **Role-Based Access**: Supports Admin, Dispatcher, Paramedic, and Public User roles with tailored functionalities.

## Technologies Used
- **Backend**: PHP
- **Frontend**: HTML, CSS, JavaScript
- **Database**: MySQL
- **Environment**: Local server (e.g., XAMPP, WAMP)

## Project Structure
```
AMBULANCE/
├── admin/
├── assets/
├── database/
├── dispatcher/
├── driver/
├── includes/
├── paramedic/
├── about.php
├── index.php
├── login.php
├── logout.php
├── patients_report.php
├── README.md
├── report-incident.php
├── services.php
```

## Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (e.g., Apache via XAMPP)
- Web browser (e.g., Chrome, Firefox)

## Setup Instructions
1. **Clone the Repository**:
2. **Set Up the Database**:
   - Create a MySQL database named `admdb`.
   - Import the provided `database.sql` file (if available) or create tables manually:
3. **Configure Database Connection**:
   - Edit `includes/db_connect.php` with your MySQL credentials:
4. **Deploy on Local Server**:
   - Place the project folder in your server's root directory (e.g., `htdocs` for XAMPP).
   - Start Apache and MySQL via XAMPP/WAMP.
5. **Access the Application**:
   - Open a browser and navigate to `http://localhost/ambulance_management`.

## Usage
- **Public Users**: Access `index.php` to report emergencies.
- **Dispatchers**: Log in to assign ambulances via `dispatcher/assign_ambulance.php`.
- **Paramedics**: Close incidents and log patient outcomes at `paramedic/close_incident.php`; view reports at `paramedic/patients_report.php`.
- **Admins**: Access `admin/patients_report.php` for comprehensive reports.
- **Navigation**: Paramedics use the sidebar (`paramedic/includes/sidebar.php`) for quick access to features.

## Contributing
1. Fork the repository.
2. Create a feature branch (`git checkout -b feature-name`).
3. Commit changes (`git commit -m 'Add feature'`).
4. Push to the branch (`git push origin feature-name`).
5. Open a pull request.

## License
This project is licensed under the MIT License.

## Contact
For issues or inquiries, please open a GitHub issue.

