<p align="center">
  <img src="banner.png" alt="Online Examination Portal" />
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Java-ED8B00?style=flat&logo=java&logoColor=white"/>
  <img src="https://img.shields.io/badge/Servlets-JavaEE-blue"/>
  <img src="https://img.shields.io/badge/JSP-JavaEE-orange"/>
  <img src="https://img.shields.io/badge/JDBC-Database-green"/>
  <img src="https://img.shields.io/badge/Oracle-SQL-red"/>
  <img src="https://img.shields.io/badge/Tomcat-Apache-yellow"/>
</p>

---

## 📝 Online Examination Portal

A **full-stack Java-based examination system** that enables secure online exams with
automatic evaluation and role-based access for students and administrators.

---

## 🚀 Project Highlights

- 🔐 Secure authentication system
- ⏱️ Timed online examinations
- ✅ Automatic evaluation
- 🗂️ Persistent result storage
- 🛠️ Admin-controlled exam management

---

## 👥 User Roles

### 👨‍🎓 Student
- Register & login securely  
- View available subjects/exams  
- Attempt timed exams  
- Get instant results  
- View previous exam results (date & score)

### 👨‍💼 Admin
- Secure admin login  
- Manage subjects & questions  
- Control exam timing  
- View student exam results  

---

## ⚙️ Features

- Authentication (Login / Registration / Change Password)
- Role-based access control
- Timed exam with auto-submit on timeout
- Dynamic question loading from Oracle DB
- Automatic answer evaluation
- Result display with correct & incorrect answers
- Student dashboard with previous attempts
- Admin dashboard for complete exam management

---

## 🧱 Tech Stack

**Backend**
- Java
- Servlets
- JSP
- JDBC
- Oracle SQL

**Frontend**
- HTML
- CSS
- JavaScript

**Tools**
- Apache Tomcat
- Eclipse IDE

---

## 🗂️ System Architecture

Client (Browser)
↓
JSP (View)
↓
Servlets (Controller)
↓
JDBC
↓
Oracle SQL Database

yaml
Copy code

✔ Separation of concerns  
✔ Scalability  
✔ Maintainability  

---

## 🛢️ Database Design (High Level)

- Users (user_id, username, password, role)
- Subjects (subject_id, subject_name)
- Questions (question_id, subject_id, options, correct_answer)
- Results (result_id, user_id, subject_id, score, date)

---

## 📸 Screenshots

> Login Page  
> Admin Dashboard  
> Student Dashboard  
> Exam Interface  
> Result Page  

📄 Detailed screenshots are included in the project documentation PDF.

*(Optional: add images directly here later for even more impact)*

---

## 🧪 Run Locally

```bash
git clone https://github.com/your-username/online-examination-portal.git
Import project into Eclipse

Configure Apache Tomcat

Set up Oracle SQL database

Update JDBC credentials

Run on server

Access:

arduino
Copy code
http://localhost:8080/OnlineExaminationPortal
🎯 Learning Outcomes
Java EE web application development

Servlets & JSP lifecycle understanding

JDBC-based database integration

Session & authentication handling

Real-world exam workflow implementation

🔮 Future Enhancements
Password hashing

Pagination for question banks

Migration to Spring Boot

Advanced result analytics

Cloud deployment

👤 Author
Om Kumar
B.Tech Computer Science
Aspiring Full-Stack Java Developer

🔗 GitHub: https://github.com/omkumar0417
🔗 LinkedIn: https://www.linkedin.com/in/omkumar0417

📌 This project emphasizes backend engineering, database interaction, and real-world system design, making it suitable for entry-level Java backend and full-stack roles.