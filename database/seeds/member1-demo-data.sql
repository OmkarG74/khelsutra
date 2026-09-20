USE khelsutra;

-- 1. Departments
INSERT INTO departments (id, organization_id, name, description, status, created_at, updated_at) VALUES
(1, 1, 'Football Department', 'Coaching, match readiness, and squad tactical training', 'active', NOW(), NOW()),
(2, 1, 'Cricket Operations', 'Pitch management, nets, and youth development', 'active', NOW(), NOW()),
(3, 1, 'Badminton & Racket Sports', 'High-performance badminton training and conditioning', 'active', NOW(), NOW()),
(4, 1, 'Medical & Physiotherapy', 'Sports injury management, rehab, and player fitness', 'active', NOW(), NOW()),
(5, 1, 'Administration & Finance', 'Academy operations, payroll, and facility logistics', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 2. Employee Categories
INSERT INTO employee_categories (id, organization_id, name, description, status, created_at, updated_at) VALUES
(1, 1, 'Head Coach', 'Senior certified coaching personnel leading championship teams', 'active', NOW(), NOW()),
(2, 1, 'Assistant Coach', 'Junior squad instructors and drill coordinators', 'active', NOW(), NOW()),
(3, 1, 'Medical Specialist', 'Physiotherapists, doctors, and sports nutritionists', 'active', NOW(), NOW()),
(4, 1, 'Operations & Management', 'Academy administrators and finance coordinators', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 3. Employees
INSERT INTO employees (
    id, organization_id, user_id, employee_code, first_name, middle_name, last_name,
    gender, blood_group, phone, email, city, state, country,
    department_id, employee_category_id, designation, joining_date,
    employment_type, employment_status,
    emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
    bank_name, bank_account_number, bank_ifsc, notes,
    created_at, updated_at
) VALUES
(1, 1, 2, 'EMP-2026-001', 'Rajesh', 'Kumar', 'Sharma', 'male', 'O+', '+919876543212', 'coach@khelsutra.com', 'Pune', 'Maharashtra', 'India', 1, 1, 'Head Coach - Football', '2022-06-15', 'full_time', 'active', 'Sunita Sharma', '+919876543299', 'Spouse', 'State Bank of India', '30982736451', 'SBIN0001234', 'AIFF Pro License holder.', NOW(), NOW()),
(2, 1, NULL, 'EMP-2026-002', 'Amit', 'Rao', 'Deshmukh', 'male', 'B+', '+919876543214', 'amit.d@khelsutra.com', 'Pune', 'Maharashtra', 'India', 2, 1, 'Head Coach - Cricket', '2023-01-10', 'full_time', 'active', 'Meera Deshmukh', '+919876543298', 'Spouse', 'HDFC Bank', '5010023456789', 'HDFC0000456', 'BCCI Level 3 Certified.', NOW(), NOW()),
(3, 1, NULL, 'EMP-2026-003', 'Priya', 'Nair', 'Menon', 'female', 'A+', '+919876543215', 'priya.m@khelsutra.com', 'Pune', 'Maharashtra', 'India', 3, 1, 'Head Coach - Badminton', '2023-04-01', 'full_time', 'active', 'Ramesh Menon', '+919876543297', 'Brother', 'ICICI Bank', '002301987654', 'ICIC0000234', 'BWF Level 2 High Performance.', NOW(), NOW()),
(4, 1, NULL, 'EMP-2026-004', 'Kavita', 'S.', 'Salunkhe', 'female', 'AB+', '+919876543216', 'kavita.s@khelsutra.com', 'Pune', 'Maharashtra', 'India', 4, 3, 'Lead Physiotherapist', '2024-02-15', 'full_time', 'active', 'Suresh Salunkhe', '+919876543296', 'Father', 'Axis Bank', '914020087654321', 'UTIB0000123', 'Specialist in sports knee and ankle rehab.', NOW(), NOW())
ON DUPLICATE KEY UPDATE employee_code = VALUES(employee_code);

-- 4. Coach Profiles (Linked to employees)
INSERT INTO coach_profiles (
    id, organization_id, employee_id, coach_code, specialization, qualification,
    certifications, experience_years, joining_date, license_number, license_expiry_date, status, notes,
    created_at, updated_at
) VALUES
(1, 1, 1, 'COACH-FB-01', 'Football Tactics & Striker Conditioning', 'NIS Diploma in Sports Coaching', 'AIFF Pro License, AFC A-Diploma', 11.5, '2022-06-15', 'AIFF-PRO-2022-984', '2027-12-31', 'active', 'Assigned to Titans U-18 squad.', NOW(), NOW()),
(2, 1, 2, 'COACH-CR-02', 'Cricket Fast Bowling & Nets Training', 'B.P.Ed (Sports Science)', 'BCCI Level 3 Coaching Certificate', 9.0, '2023-01-10', 'BCCI-L3-2023-412', '2028-06-30', 'active', 'Assigned to Strikers U-14 squad.', NOW(), NOW()),
(3, 1, 3, 'COACH-BD-03', 'Badminton Singles Agility & Footwork', 'National Champion 2018, NIS Certified', 'BWF Level 2 High Performance', 7.5, '2023-04-01', 'BWF-HP-2023-119', '2027-05-31', 'active', 'Assigned to Apex Senior Squad.', NOW(), NOW())
ON DUPLICATE KEY UPDATE coach_code = VALUES(coach_code);

-- 5. Leave Types
INSERT INTO leave_types (id, organization_id, name, description, max_days_per_year, status, created_at, updated_at) VALUES
(1, 1, 'Casual Leave', 'Standard casual day off for personal matters', 12.00, 'active', NOW(), NOW()),
(2, 1, 'Medical / Sick Leave', 'Absence due to illness or medical consultations', 10.00, 'active', NOW(), NOW()),
(3, 1, 'Tournament Duty Leave', 'Official on-duty leave representing academy in tournaments', 20.00, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 6. Leave Requests (matching the dashboard's 2 Pending Leave KPI card)
INSERT INTO leave_requests (
    id, organization_id, applicant_type, employee_id, athlete_id, leave_type_id,
    start_date, end_date, total_days, reason, status, created_at, updated_at
) VALUES
(1, 1, 'employee', 1, NULL, 1, '2026-09-26', '2026-09-27', 2.00, 'Family ceremony in hometown', 'pending', NOW(), NOW()),
(2, 1, 'employee', 4, NULL, 2, '2026-09-24', '2026-09-24', 1.00, 'Attending National Sports Medicine Conference', 'pending', NOW(), NOW())
ON DUPLICATE KEY UPDATE reason = VALUES(reason);

-- 7. Salary Structures
INSERT INTO salary_structures (
    id, organization_id, employee_id, effective_from, basic_salary, allowances, deduction,
    overtime_rate, bonus_default, tax_default, other_deductions_default, status, created_at, updated_at
) VALUES
(1, 1, 1, '2026-01-01', 45000.00, 8000.00, 1500.00, 450.00, 5000.00, 3200.00, 500.00, 'active', NOW(), NOW()),
(2, 1, 2, '2026-01-01', 42000.00, 7500.00, 1200.00, 400.00, 4000.00, 2800.00, 400.00, 'active', NOW(), NOW()),
(3, 1, 3, '2026-01-01', 40000.00, 7000.00, 1000.00, 380.00, 4000.00, 2500.00, 300.00, 'active', NOW(), NOW()),
(4, 1, 4, '2026-01-01', 35000.00, 6000.00, 800.00, 300.00, 3000.00, 2000.00, 200.00, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE basic_salary = VALUES(basic_salary);

-- 8. Payroll Periods
INSERT INTO payroll_periods (
    id, organization_id, period_name, start_date, end_date, status, processed_by, processed_at, created_at, updated_at
) VALUES
(1, 1, 'August 2026', '2026-08-01', '2026-08-31', 'locked', 1, '2026-08-31 18:00:00', NOW(), NOW()),
(2, 1, 'September 2026', '2026-09-01', '2026-09-30', 'processing', 1, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE period_name = VALUES(period_name);

-- 9. Payroll Records for August (Locked/Paid) and September (Processing)
INSERT INTO payroll (
    organization_id, payroll_period_id, employee_id,
    basic_salary, allowances, overtime_hours, overtime_amount, bonus, tax, deductions, other_deductions,
    gross_salary, net_salary, payment_status, payment_date, payment_reference, remarks,
    created_at, updated_at
) VALUES
(1, 1, 1, 45000.00, 8000.00, 4.00, 1800.00, 5000.00, 3200.00, 1500.00, 500.00, 59800.00, 54600.00, 'paid', '2026-08-31', 'PAY-202608-001', 'Disbursed via direct NEFT', NOW(), NOW()),
(1, 1, 2, 42000.00, 7500.00, 2.00, 800.00, 4000.00, 2800.00, 1200.00, 400.00, 54300.00, 49900.00, 'paid', '2026-08-31', 'PAY-202608-002', 'Disbursed via direct NEFT', NOW(), NOW()),
(1, 2, 1, 45000.00, 8000.00, 0.00, 0.00, 5000.00, 3200.00, 1500.00, 500.00, 58000.00, 52800.00, 'pending', NULL, NULL, 'September payroll in calculation', NOW(), NOW()),
(1, 2, 2, 42000.00, 7500.00, 0.00, 0.00, 4000.00, 2800.00, 1200.00, 400.00, 53500.00, 49100.00, 'pending', NULL, NULL, 'September payroll in calculation', NOW(), NOW())
ON DUPLICATE KEY UPDATE gross_salary = VALUES(gross_salary);

-- 10. Organization Settings
INSERT INTO organization_settings (organization_id, setting_key, setting_value, setting_type, created_at, updated_at) VALUES
(1, 'academic_timezone', 'Asia/Kolkata', 'string', NOW(), NOW()),
(1, 'currency_symbol', 'INR (₹)', 'string', NOW(), NOW()),
(1, 'attendance_threshold_percentage', '75', 'integer', NOW(), NOW()),
(1, 'auto_approve_leave_within_days', '2', 'integer', NOW(), NOW()),
(1, 'enable_biometric_sync', '1', 'boolean', NOW(), NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
