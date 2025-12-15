📝 Online Examination Portal

A full-stack web-based examination system developed using Java Servlets, JSP, JDBC, and Oracle SQL, designed to conduct secure online exams with automatic evaluation and role-based access for students and administrators.

🚀 Project Overview

The Online Examination Portal digitizes the traditional examination process by providing a centralized, secure, and efficient platform for conducting exams online.

The system supports:

Secure authentication

Timed online exams

Automatic evaluation

Persistent result storage

Admin-controlled exam and question management

This project demonstrates core backend engineering concepts and real-world web application design using Java EE technologies.

🧑‍💻 User Roles
👨‍🎓 Student

Register and log in securely

View available subjects/exams

Attempt timed online exams

Receive instant results after submission

View previous exam results with date and score

👨‍💼 Admin

Secure admin login

Add, update, and delete subjects and questions

Manage student records

Control exam configuration and time limits

View student exam results

⚙️ Features

🔐 Authentication system (Login / Registration / Change Password)

🧭 Role-based access control (Admin & Student)

⏱️ Timed exam interface with auto-submit on timeout

📄 Dynamic question loading from database

✅ Automatic evaluation of answers

📊 Result display with correct & incorrect answers

🗂️ Student dashboard showing previous exam attempts

🛠️ Admin dashboard for complete exam management

🧱 Tech Stack
Backend

Java

Servlets

JSP

JDBC

Oracle SQL

Frontend

HTML

CSS

JavaScript

Tools & Server

Apache Tomcat

Eclipse IDE

🗂️ System Architecture
Client (Browser)
     ↓
JSP Pages (View)
     ↓
Servlets (Controller)
     ↓
JDBC
     ↓
Oracle SQL Database


This MVC-based structure ensures:

Separation of concerns

Scalability

Maintainability

🛢️ Database Design (High Level)

Users (user_id, username, password, role)

Subjects (subject_id, subject_name)

Questions (question_id, subject_id, options, correct_answer)

Results (result_id, user_id, subject_id, score, date)

📸 Screenshots

Screenshots of Login Page, Admin Dashboard, Student Dashboard, Exam Interface, and Result Page are included in the project documentation PDF.

🧪 How to Run the Project Locally

Clone the repository

git clone https://github.com/your-username/online-examination-portal.git


Import the project into Eclipse IDE

Configure Apache Tomcat Server

Set up Oracle SQL database

Create required tables

Update database credentials in JDBC configuration

Run the project on Tomcat server

Access in browser:

http://localhost:8080/OnlineExaminationPortal

🎯 Learning Outcomes

Hands-on experience with Java EE web applications

Deep understanding of Servlets & JSP lifecycle

Practical use of JDBC for database interaction

Session management and authentication handling

Building real-world CRUD-based systems

Implementing timed workflows and automatic evaluation logic

🔮 Future Enhancements

Password hashing for enhanced security

Pagination for large question banks

REST API migration (Spring Boot)

Analytics dashboards

Deployment on cloud infrastructure

👤 Author

Om Kumar
B.Tech Computer Science Student
Aspiring Full-Stack Java Developer

GitHub: https://github.com/omkumar0417

LinkedIn: https://www.linkedin.com/in/omkumar0417

📌 Note for Recruiters

This project focuses on backend fundamentals, database interaction, and system design, making it suitable for entry-level Java backend and full-stack roles.