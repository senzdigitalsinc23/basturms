# Complete Staff Management API Endpoints

## Overview
Complete API documentation for staff management including registration, updates, deletion, and assignments.

---

## 1. Staff CRUD Operations

### 1.1 Register Staff
**POST** `/api/v1/staff/register`

Register a new staff member with complete details.

**Request Body:**
```json
{
  "personal_contact": {
    "first_name": "Joseph",
    "last_name": "Konnie",
    "other_name": "",
    "email": "joseph.konnie@basturms.com",
    "phone": "0247760226",
    "id_type": "1",
    "id_no": "GHA-718881425-1",
    "snnit_no": "1234567879898987",
    "date_of_joining": "2026-01-01",
    "status": "active"
  },
  "address": {
    "country": "GH",
    "city": "Tarkwa",
    "hometown": "Dompim Pepesa",
    "residence": "Dompim",
    "house_no": "DP21",
    "gps_no": "WT-2018-0191"
  },
  "academic_history": [
    {
      "school_name": "University of Ghana",
      "program_offered": "Bsc. Agricultural Science",
      "qualification": "Bsc Agric",
      "year_completed": "2020"
    }
  ],
  "appointment_history": {
    "appointment_date": "2026-02-20",
    "appointment_status": "appointed",
    "class_teacher_for": "jhs1",
    "assigned_classes": [
      {"class_id": "jhs1"},
      {"class_id": "jhs2"}
    ],
    "assigned_subjects": [
     