-- ============================================================
-- SPORTS MANAGEMENT SOFTWARE
-- Multi-tenant MySQL 8.0+ Database Schema
-- Version: 1.0
-- ============================================================
-- Design principles:
-- 1. Super Admin is the software provider and can manage multiple organisations.
-- 2. Every organisation is isolated through organisation_id.
-- 3. Roles are fixed by the application; permissions are role-based.
-- 4. Coaches are employees/staff and also have a coach profile.
-- 5. Athletes have one current primary sport, with assignment history.
-- 6. Important records use soft deletes where appropriate.
-- 7. Files are represented by paths only; binary files are not stored in MySQL.
-- 8. Performance metrics are configurable by sport.
-- 9. Tournament formats and levels are configurable reference data.
-- ============================================================

CREATE DATABASE IF NOT EXISTS khelsutra
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;



SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 01. TENANCY / AUTHENTICATION / RBAC
-- ============================================================

CREATE TABLE organizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    legal_name VARCHAR(200) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    website VARCHAR(255) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    logo_path VARCHAR(500) NULL,
    status ENUM('pending','active','suspended','expired','inactive') NOT NULL DEFAULT 'pending',
    access_start_date DATE NULL,
    access_end_date DATE NULL,
    plan_name VARCHAR(100) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_org_status (status),
    INDEX idx_org_access (access_start_date, access_end_date)
);

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_system_role BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    module VARCHAR(80) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permission_module_action (module, action)
);

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    username VARCHAR(100) NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    profile_photo_path VARCHAR(500) NULL,
    status ENUM('active','inactive','locked','pending') NOT NULL DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_user_status (status)
);

CREATE TABLE organization_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NULL,
    athlete_id BIGINT UNSIGNED NULL,
    access_status ENUM('active','inactive','suspended','pending') NOT NULL DEFAULT 'active',
    assigned_by BIGINT UNSIGNED NULL,
    assigned_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_org_user (organization_id, user_id),
    INDEX idx_org_role (organization_id, role_id),
    INDEX idx_org_access (organization_id, access_status)
);

CREATE TABLE user_permission_overrides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    override_type ENUM('grant','deny') NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_permission_override (organization_id, user_id, permission_id),
    CONSTRAINT fk_upo_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_upo_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_upo_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE organization_access_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    action ENUM('created','activated','suspended','expired','renewed','deactivated') NOT NULL,
    previous_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NULL,
    access_start_date DATE NULL,
    access_end_date DATE NULL,
    performed_by BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_org_access_log (organization_id, created_at),
    CONSTRAINT fk_oal_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- ============================================================
-- 02. REFERENCE / SPORTS
-- ============================================================

CREATE TABLE sports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(30) NULL,
    description TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    is_global BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_sport_org_name (organization_id, name),
    INDEX idx_sport_status (status)
);

CREATE TABLE sport_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender ENUM('male','female','mixed','open','not_specified') NOT NULL DEFAULT 'open',
    min_age SMALLINT UNSIGNED NULL,
    max_age SMALLINT UNSIGNED NULL,
    description TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_sport_category (organization_id, sport_id, name),
    CONSTRAINT fk_sport_cat_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_sport_cat_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
);

CREATE TABLE performance_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sport_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL,
    metric_type ENUM('number','decimal','percentage','time','distance','rating','text') NOT NULL DEFAULT 'number',
    unit VARCHAR(30) NULL,
    min_value DECIMAL(12,4) NULL,
    max_value DECIMAL(12,4) NULL,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_metric_sport_code (sport_id, code),
    CONSTRAINT fk_metric_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
);

CREATE TABLE tournament_levels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active'
);

CREATE TABLE tournament_formats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active'
);

-- ============================================================
-- 03. EMPLOYEES / HR / COACHES
-- ============================================================

CREATE TABLE departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_department_org_name (organization_id, name),
    CONSTRAINT fk_department_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE employee_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_employee_category_org_name (organization_id, name),
    CONSTRAINT fk_employee_category_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    employee_code VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    photo_path VARCHAR(500) NULL,
    date_of_birth DATE NULL,
    gender ENUM('male','female','other','not_specified') NOT NULL DEFAULT 'not_specified',
    blood_group VARCHAR(10) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    department_id BIGINT UNSIGNED NULL,
    employee_category_id BIGINT UNSIGNED NULL,
    designation VARCHAR(100) NULL,
    joining_date DATE NULL,
    employment_type ENUM('full_time','part_time','contract','temporary','intern','other') NOT NULL DEFAULT 'full_time',
    employment_status ENUM('active','inactive','on_leave','terminated') NOT NULL DEFAULT 'active',
    emergency_contact_name VARCHAR(150) NULL,
    emergency_contact_phone VARCHAR(30) NULL,
    emergency_contact_relationship VARCHAR(50) NULL,
    bank_name VARCHAR(150) NULL,
    bank_account_number VARCHAR(100) NULL,
    bank_ifsc VARCHAR(20) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_employee_org_code (organization_id, employee_code),
    INDEX idx_employee_org_status (organization_id, employment_status),
    CONSTRAINT fk_employee_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_employee_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_employee_category FOREIGN KEY (employee_category_id) REFERENCES employee_categories(id) ON DELETE SET NULL
);

CREATE TABLE coach_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    coach_code VARCHAR(50) NOT NULL,
    specialization VARCHAR(150) NULL,
    qualification TEXT NULL,
    certifications TEXT NULL,
    experience_years DECIMAL(5,2) NULL,
    joining_date DATE NULL,
    license_number VARCHAR(100) NULL,
    license_expiry_date DATE NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_coach_org_code (organization_id, coach_code),
    UNIQUE KEY uq_coach_employee (employee_id),
    CONSTRAINT fk_coach_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_coach_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE employee_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_number VARCHAR(100) NULL,
    file_path VARCHAR(500) NOT NULL,
    issue_date DATE NULL,
    expiry_date DATE NULL,
    notes TEXT NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_employee_document (employee_id),
    CONSTRAINT fk_emp_doc_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_emp_doc_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- ============================================================
-- 04. ATHLETES / GUARDIANS / DOCUMENTS
-- ============================================================

CREATE TABLE athletes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    athlete_code VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    photo_path VARCHAR(500) NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male','female','other','not_specified') NOT NULL DEFAULT 'not_specified',
    blood_group VARCHAR(10) NULL,
    nationality VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    government_id_type VARCHAR(50) NULL,
    government_id_number VARCHAR(100) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    registration_date DATE NULL,
    joining_date DATE NULL,
    current_sport_id BIGINT UNSIGNED NULL,
    current_category_id BIGINT UNSIGNED NULL,
    status ENUM('active','inactive','injured','suspended','retired') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_athlete_org_code (organization_id, athlete_code),
    INDEX idx_athlete_org_status (organization_id, status),
    CONSTRAINT fk_athlete_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_athlete_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_athlete_sport FOREIGN KEY (current_sport_id) REFERENCES sports(id) ON DELETE SET NULL,
    CONSTRAINT fk_athlete_category FOREIGN KEY (current_category_id) REFERENCES sport_categories(id) ON DELETE SET NULL
);

CREATE TABLE athlete_guardians (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    guardian_type ENUM('father','mother','guardian','other') NOT NULL DEFAULT 'guardian',
    full_name VARCHAR(150) NOT NULL,
    relationship VARCHAR(50) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    alternate_phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    occupation VARCHAR(100) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    is_emergency_contact BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_guardian_athlete (athlete_id),
    CONSTRAINT fk_guardian_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_guardian_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE
);

CREATE TABLE athlete_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM(
        'id_proof',
        'birth_certificate',
        'medical_certificate',
        'insurance',
        'sports_certificate',
        'consent_form',
        'other'
    ) NOT NULL,
    document_name VARCHAR(150) NOT NULL,
    document_number VARCHAR(100) NULL,
    file_path VARCHAR(500) NOT NULL,
    issue_date DATE NULL,
    expiry_date DATE NULL,
    notes TEXT NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_athlete_document (athlete_id, document_type),
    CONSTRAINT fk_ath_doc_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ath_doc_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE
);

CREATE TABLE athlete_sport_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_athlete_sport_history (athlete_id, start_date),
    CONSTRAINT fk_ash_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ash_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ash_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT,
    CONSTRAINT fk_ash_category FOREIGN KEY (category_id) REFERENCES sport_categories(id) ON DELETE SET NULL
);

-- ============================================================
-- 05. TEAMS / ASSIGNMENTS
-- ============================================================

CREATE TABLE teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    team_code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    gender ENUM('male','female','mixed','open','not_specified') NOT NULL DEFAULT 'open',
    age_group VARCHAR(50) NULL,
    formation_or_level VARCHAR(100) NULL,
    description TEXT NULL,
    status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_team_org_code (organization_id, team_code),
    CONSTRAINT fk_team_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT,
    CONSTRAINT fk_team_category FOREIGN KEY (category_id) REFERENCES sport_categories(id) ON DELETE SET NULL
);

CREATE TABLE team_coaches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    coach_id BIGINT UNSIGNED NOT NULL,
    coach_role ENUM('head_coach','assistant_coach','fitness_coach','other') NOT NULL DEFAULT 'head_coach',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_team_coach (team_id, coach_id),
    CONSTRAINT fk_team_coach_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_coach_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_coach_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE RESTRICT
);

CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    jersey_number VARCHAR(20) NULL,
    position VARCHAR(100) NULL,
    member_role ENUM('player','captain','vice_captain','other') NOT NULL DEFAULT 'player',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    is_current BOOLEAN NOT NULL DEFAULT TRUE,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_team_member_current (team_id, is_current),
    INDEX idx_athlete_team_history (athlete_id, start_date),
    CONSTRAINT fk_team_member_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_member_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_member_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE RESTRICT
);

-- ============================================================
-- 06. VENUES / FACILITIES / BOOKING / MAINTENANCE / HOUSEKEEPING
-- ============================================================

CREATE TABLE venues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    venue_code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    venue_type VARCHAR(100) NULL,
    description TEXT NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    capacity INT UNSIGNED NULL,
    opening_time TIME NULL,
    closing_time TIME NULL,
    status ENUM('active','inactive','under_maintenance') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_venue_org_code (organization_id, venue_code),
    CONSTRAINT fk_venue_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE venue_facilities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    facility_type VARCHAR(100) NULL,
    description TEXT NULL,
    capacity INT UNSIGNED NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    status ENUM('active','inactive','under_maintenance') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_facility_venue (venue_id),
    CONSTRAINT fk_facility_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_facility_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
);

CREATE TABLE venue_bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    facility_id BIGINT UNSIGNED NULL,
    booking_reference VARCHAR(50) NOT NULL,
    booked_by_user_id BIGINT UNSIGNED NOT NULL,
    booking_type VARCHAR(100) NULL,
    purpose VARCHAR(255) NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    event_id BIGINT UNSIGNED NULL,
    tournament_id BIGINT UNSIGNED NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_booking_reference (organization_id, booking_reference),
    INDEX idx_booking_schedule (venue_id, facility_id, booking_date, start_time, end_time),
    CONSTRAINT fk_booking_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_facility FOREIGN KEY (facility_id) REFERENCES venue_facilities(id) ON DELETE SET NULL,
    CONSTRAINT fk_booking_user FOREIGN KEY (booked_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);

CREATE TABLE venue_maintenance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    facility_id BIGINT UNSIGNED NULL,
    maintenance_reference VARCHAR(50) NOT NULL,
    issue_title VARCHAR(200) NOT NULL,
    issue_description TEXT NULL,
    priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    reported_by BIGINT UNSIGNED NULL,
    assigned_employee_id BIGINT UNSIGNED NULL,
    assigned_vendor_id BIGINT UNSIGNED NULL,
    scheduled_date DATE NULL,
    completed_date DATE NULL,
    estimated_cost DECIMAL(14,2) NULL DEFAULT 0.00,
    actual_cost DECIMAL(14,2) NULL DEFAULT 0.00,
    status ENUM('reported','assigned','in_progress','completed','cancelled') NOT NULL DEFAULT 'reported',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_maintenance_reference (organization_id, maintenance_reference),
    CONSTRAINT fk_vm_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_vm_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE RESTRICT,
    CONSTRAINT fk_vm_facility FOREIGN KEY (facility_id) REFERENCES venue_facilities(id) ON DELETE SET NULL,
    CONSTRAINT fk_vm_employee FOREIGN KEY (assigned_employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE TABLE housekeeping_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    facility_id BIGINT UNSIGNED NULL,
    task_reference VARCHAR(50) NOT NULL,
    task_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    assigned_employee_id BIGINT UNSIGNED NULL,
    scheduled_date DATE NOT NULL,
    scheduled_start_time TIME NULL,
    scheduled_end_time TIME NULL,
    completed_at TIMESTAMP NULL,
    status ENUM('pending','assigned','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_housekeeping_reference (organization_id, task_reference),
    CONSTRAINT fk_housekeeping_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_housekeeping_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE RESTRICT,
    CONSTRAINT fk_housekeeping_facility FOREIGN KEY (facility_id) REFERENCES venue_facilities(id) ON DELETE SET NULL,
    CONSTRAINT fk_housekeeping_employee FOREIGN KEY (assigned_employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

-- ============================================================
-- 07. TRAINING / CAMPS / ATTENDANCE / PERFORMANCE
-- ============================================================

CREATE TABLE training_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    training_reference VARCHAR(50) NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    coach_id BIGINT UNSIGNED NULL,
    venue_id BIGINT UNSIGNED NULL,
    facility_id BIGINT UNSIGNED NULL,
    training_type VARCHAR(100) NULL,
    title VARCHAR(200) NOT NULL,
    objectives TEXT NULL,
    training_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_training_reference (organization_id, training_reference),
    CONSTRAINT fk_training_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_training_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE RESTRICT,
    CONSTRAINT fk_training_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL,
    CONSTRAINT fk_training_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
    CONSTRAINT fk_training_facility FOREIGN KEY (facility_id) REFERENCES venue_facilities(id) ON DELETE SET NULL
);

CREATE TABLE training_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    training_session_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NULL,
    coach_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    attendance_status ENUM('present','absent','late','excused') NOT NULL,
    check_in_time TIME NULL,
    check_out_time TIME NULL,
    remarks TEXT NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_training_attendee (training_session_id, athlete_id, coach_id, employee_id),
    INDEX idx_training_attendance_athlete (athlete_id),
    CONSTRAINT fk_ta_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_training FOREIGN KEY (training_session_id) REFERENCES training_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL,
    CONSTRAINT fk_ta_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE TABLE training_camps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    camp_reference VARCHAR(50) NOT NULL,
    name VARCHAR(200) NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    location_name VARCHAR(200) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    objectives TEXT NULL,
    coach_id BIGINT UNSIGNED NULL,
    status ENUM('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_camp_reference (organization_id, camp_reference),
    CONSTRAINT fk_camp_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_camp_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT,
    CONSTRAINT fk_camp_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    CONSTRAINT fk_camp_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL
);

CREATE TABLE training_camp_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    training_camp_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    coach_id BIGINT UNSIGNED NULL,
    participant_role VARCHAR(80) NULL,
    status ENUM('registered','confirmed','cancelled','completed') NOT NULL DEFAULT 'registered',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_camp_participant (training_camp_id, athlete_id, employee_id, coach_id),
    CONSTRAINT fk_tcp_camp FOREIGN KEY (training_camp_id) REFERENCES training_camps(id) ON DELETE CASCADE,
    CONSTRAINT fk_tcp_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_tcp_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_tcp_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL
);

CREATE TABLE athlete_performance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    training_session_id BIGINT UNSIGNED NULL,
    match_id BIGINT UNSIGNED NULL,
    evaluation_date DATE NOT NULL,
    overall_rating DECIMAL(5,2) NULL,
    coach_remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_performance_athlete_date (athlete_id, evaluation_date),
    CONSTRAINT fk_perf_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_perf_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_perf_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT,
    CONSTRAINT fk_perf_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    CONSTRAINT fk_perf_training FOREIGN KEY (training_session_id) REFERENCES training_sessions(id) ON DELETE SET NULL
);

CREATE TABLE athlete_performance_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performance_id BIGINT UNSIGNED NOT NULL,
    metric_id BIGINT UNSIGNED NOT NULL,
    numeric_value DECIMAL(14,4) NULL,
    text_value TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_performance_metric (performance_id, metric_id),
    CONSTRAINT fk_pv_performance FOREIGN KEY (performance_id) REFERENCES athlete_performance(id) ON DELETE CASCADE,
    CONSTRAINT fk_pv_metric FOREIGN KEY (metric_id) REFERENCES performance_metrics(id) ON DELETE RESTRICT
);

-- ============================================================
-- 08. MEDICAL
-- ============================================================

CREATE TABLE athlete_medical_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    allergies TEXT NULL,
    chronic_conditions TEXT NULL,
    current_medications TEXT NULL,
    medical_notes TEXT NULL,
    emergency_notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_medical_profile_athlete (athlete_id),
    CONSTRAINT fk_medical_profile_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_medical_profile_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE
);

CREATE TABLE medical_visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    visit_date DATE NOT NULL,
    doctor_name VARCHAR(150) NULL,
    medical_facility VARCHAR(200) NULL,
    reason TEXT NULL,
    diagnosis TEXT NULL,
    treatment TEXT NULL,
    medications TEXT NULL,
    follow_up_date DATE NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_med_visit_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_med_visit_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE RESTRICT
);

CREATE TABLE athlete_injuries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    injury_date DATE NOT NULL,
    injury_type VARCHAR(150) NOT NULL,
    body_part VARCHAR(100) NULL,
    severity ENUM('minor','moderate','major','critical') NULL,
    description TEXT NULL,
    treatment TEXT NULL,
    expected_recovery_date DATE NULL,
    actual_recovery_date DATE NULL,
    status ENUM('active','recovering','recovered','chronic') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_injury_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_injury_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE RESTRICT
);

CREATE TABLE athlete_medical_clearances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    clearance_date DATE NOT NULL,
    valid_until DATE NULL,
    doctor_name VARCHAR(150) NULL,
    clearance_status ENUM('fit','fit_with_restrictions','unfit') NOT NULL,
    restrictions TEXT NULL,
    certificate_path VARCHAR(500) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_clearance_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_clearance_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE RESTRICT
);

-- ============================================================
-- 09. TOURNAMENTS / FIXTURES / MATCHES / RESULTS
-- ============================================================

CREATE TABLE tournaments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    tournament_reference VARCHAR(50) NOT NULL,
    name VARCHAR(200) NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    tournament_level_id BIGINT UNSIGNED NULL,
    tournament_format_id BIGINT UNSIGNED NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    location_name VARCHAR(200) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    organizer_name VARCHAR(200) NULL,
    description TEXT NULL,
    rules TEXT NULL,
    status ENUM('draft','registration_open','registration_closed','ongoing','completed','cancelled') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_tournament_reference (organization_id, tournament_reference),
    INDEX idx_tournament_dates (organization_id, start_date, end_date),
    CONSTRAINT fk_tournament_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_tournament_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tournament_category FOREIGN KEY (category_id) REFERENCES sport_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_tournament_level FOREIGN KEY (tournament_level_id) REFERENCES tournament_levels(id) ON DELETE SET NULL,
    CONSTRAINT fk_tournament_format FOREIGN KEY (tournament_format_id) REFERENCES tournament_formats(id) ON DELETE SET NULL
);

CREATE TABLE tournament_teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    registration_number VARCHAR(50) NULL,
    seed_number INT UNSIGNED NULL,
    group_name VARCHAR(50) NULL,
    status ENUM('registered','approved','withdrawn','eliminated','qualified','winner','runner_up') NOT NULL DEFAULT 'registered',
    registered_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tournament_team (tournament_id, team_id),
    CONSTRAINT fk_tt_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_tt_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE RESTRICT
);

CREATE TABLE tournament_venues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tournament_venue (tournament_id, venue_id),
    CONSTRAINT fk_tv_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_tv_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE RESTRICT
);

CREATE TABLE fixtures (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    tournament_id BIGINT UNSIGNED NOT NULL,
    fixture_reference VARCHAR(50) NOT NULL,
    round_name VARCHAR(100) NULL,
    group_name VARCHAR(50) NULL,
    home_team_id BIGINT UNSIGNED NOT NULL,
    away_team_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NULL,
    facility_id BIGINT UNSIGNED NULL,
    scheduled_date DATE NOT NULL,
    scheduled_start_time TIME NOT NULL,
    scheduled_end_time TIME NULL,
    status ENUM('scheduled','postponed','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_fixture_reference (organization_id, fixture_reference),
    INDEX idx_fixture_schedule (scheduled_date, scheduled_start_time),
    CONSTRAINT fk_fixture_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_fixture_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_fixture_home_team FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
    CONSTRAINT fk_fixture_away_team FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
    CONSTRAINT fk_fixture_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
    CONSTRAINT fk_fixture_facility FOREIGN KEY (facility_id) REFERENCES venue_facilities(id) ON DELETE SET NULL
);

CREATE TABLE matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    fixture_id BIGINT UNSIGNED NOT NULL,
    match_reference VARCHAR(50) NOT NULL,
    actual_start_time DATETIME NULL,
    actual_end_time DATETIME NULL,
    home_score DECIMAL(10,2) NULL,
    away_score DECIMAL(10,2) NULL,
    winner_team_id BIGINT UNSIGNED NULL,
    result_type ENUM('home_win','away_win','draw','tie','no_result','abandoned') NULL,
    referee_name VARCHAR(150) NULL,
    officials_notes TEXT NULL,
    match_notes TEXT NULL,
    status ENUM('scheduled','live','completed','abandoned') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_match_reference (organization_id, match_reference),
    UNIQUE KEY uq_match_fixture (fixture_id),
    CONSTRAINT fk_match_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_fixture FOREIGN KEY (fixture_id) REFERENCES fixtures(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_winner FOREIGN KEY (winner_team_id) REFERENCES teams(id) ON DELETE SET NULL
);

CREATE TABLE tournament_standings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    played INT UNSIGNED NOT NULL DEFAULT 0,
    won INT UNSIGNED NOT NULL DEFAULT 0,
    drawn INT UNSIGNED NOT NULL DEFAULT 0,
    lost INT UNSIGNED NOT NULL DEFAULT 0,
    points DECIMAL(10,2) NOT NULL DEFAULT 0,
    scored DECIMAL(12,2) NOT NULL DEFAULT 0,
    conceded DECIMAL(12,2) NOT NULL DEFAULT 0,
    difference DECIMAL(12,2) NOT NULL DEFAULT 0,
    rank_position INT UNSIGNED NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_standing_team (tournament_id, team_id),
    CONSTRAINT fk_standing_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_standing_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- ============================================================
-- 10. AWARDS / ACHIEVEMENTS
-- ============================================================

CREATE TABLE achievements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    tournament_id BIGINT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    achievement_type VARCHAR(100) NULL,
    position_or_medal VARCHAR(100) NULL,
    achievement_date DATE NULL,
    description TEXT NULL,
    certificate_path VARCHAR(500) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_achievement_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_achievement_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_achievement_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE SET NULL
);

CREATE TABLE awards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    award_type VARCHAR(100) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_award_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE athlete_awards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    award_id BIGINT UNSIGNED NOT NULL,
    award_date DATE NULL,
    description TEXT NULL,
    certificate_path VARCHAR(500) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_athlete_award_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_athlete_award_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_athlete_award_award FOREIGN KEY (award_id) REFERENCES awards(id) ON DELETE RESTRICT
);

-- ============================================================
-- 11. STAFF ATTENDANCE / LEAVE / PAYROLL
-- ============================================================

CREATE TABLE leave_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    max_days_per_year DECIMAL(6,2) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_leave_type_org_name (organization_id, name),
    CONSTRAINT fk_leave_type_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE leave_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    applicant_type ENUM('employee','athlete') NOT NULL,
    employee_id BIGINT UNSIGNED NULL,
    athlete_id BIGINT UNSIGNED NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(6,2) NOT NULL,
    reason TEXT NULL,
    attachment_path VARCHAR(500) NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_leave_org_status (organization_id, status),
    CONSTRAINT fk_leave_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_leave_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_leave_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_leave_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT
);

CREATE TABLE match_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    match_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NULL,
    coach_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    attendance_status ENUM('present','absent','late','excused') NOT NULL,
    remarks TEXT NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_match_attendee (match_id, athlete_id, coach_id, employee_id),
    CONSTRAINT fk_ma_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ma_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_ma_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_ma_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL,
    CONSTRAINT fk_ma_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE TABLE salary_structures (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    effective_from DATE NOT NULL,
    basic_salary DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    allowances DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    deduction DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    overtime_rate DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    bonus_default DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    tax_default DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    other_deductions_default DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_salary_employee_effective (employee_id, effective_from),
    CONSTRAINT fk_salary_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_salary_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE payroll_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    period_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft','processing','processed','locked') NOT NULL DEFAULT 'draft',
    processed_by BIGINT UNSIGNED NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payroll_period (organization_id, start_date, end_date),
    CONSTRAINT fk_payroll_period_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE payroll (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    payroll_period_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    basic_salary DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    allowances DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    overtime_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    overtime_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    bonus DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    deductions DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    other_deductions DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    gross_salary DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    net_salary DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('pending','processed','paid','cancelled') NOT NULL DEFAULT 'pending',
    payment_date DATE NULL,
    payment_reference VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payroll_employee_period (payroll_period_id, employee_id),
    CONSTRAINT fk_payroll_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_payroll_period FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id) ON DELETE RESTRICT,
    CONSTRAINT fk_payroll_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT
);

-- ============================================================
-- 12. INVENTORY / EQUIPMENT
-- ============================================================

CREATE TABLE inventory_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_inventory_category (organization_id, name),
    CONSTRAINT fk_inventory_category_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE inventory_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    item_code VARCHAR(50) NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    unit VARCHAR(30) NOT NULL DEFAULT 'piece',
    quantity DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    minimum_stock_level DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    reorder_level DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    location_name VARCHAR(150) NULL,
    status ENUM('active','inactive','discontinued') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_inventory_item_code (organization_id, item_code),
    CONSTRAINT fk_inventory_item_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_item_category FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE RESTRICT
);

CREATE TABLE equipment (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    inventory_item_id BIGINT UNSIGNED NULL,
    asset_code VARCHAR(50) NOT NULL,
    equipment_name VARCHAR(150) NOT NULL,
    serial_number VARCHAR(100) NULL,
    model_number VARCHAR(100) NULL,
    manufacturer VARCHAR(150) NULL,
    purchase_date DATE NULL,
    purchase_cost DECIMAL(14,2) NULL,
    warranty_expiry_date DATE NULL,
    condition_status ENUM('new','good','damaged','under_maintenance','lost','disposed') NOT NULL DEFAULT 'new',
    current_location VARCHAR(150) NULL,
    status ENUM('available','assigned','maintenance','lost','disposed') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_equipment_asset_code (organization_id, asset_code),
    UNIQUE KEY uq_equipment_serial (organization_id, serial_number),
    CONSTRAINT fk_equipment_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_equipment_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL
);

CREATE TABLE equipment_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    equipment_id BIGINT UNSIGNED NOT NULL,
    assignee_type ENUM('athlete','coach','employee','team','venue') NOT NULL,
    athlete_id BIGINT UNSIGNED NULL,
    coach_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    team_id BIGINT UNSIGNED NULL,
    venue_id BIGINT UNSIGNED NULL,
    assigned_date DATE NOT NULL,
    expected_return_date DATE NULL,
    returned_date DATE NULL,
    condition_on_issue VARCHAR(100) NULL,
    condition_on_return VARCHAR(100) NULL,
    status ENUM('assigned','returned','lost','damaged') NOT NULL DEFAULT 'assigned',
    issued_by BIGINT UNSIGNED NULL,
    received_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ea_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_ea_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE RESTRICT,
    CONSTRAINT fk_ea_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_ea_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL,
    CONSTRAINT fk_ea_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_ea_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    CONSTRAINT fk_ea_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL
);

CREATE TABLE stock_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    inventory_item_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('opening','purchase','issue','return','adjustment','damage','loss','disposal') NOT NULL,
    quantity DECIMAL(14,2) NOT NULL,
    unit_cost DECIMAL(14,2) NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    performed_by BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stock_item_date (inventory_item_id, transaction_date),
    CONSTRAINT fk_stock_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT
);

-- ============================================================
-- 13. VENDORS / PURCHASING / GOODS RECEIPT / INVOICES
-- ============================================================

CREATE TABLE vendors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    vendor_code VARCHAR(50) NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(150) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    alternate_phone VARCHAR(30) NULL,
    gst_number VARCHAR(30) NULL,
    pan_number VARCHAR(30) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    bank_name VARCHAR(150) NULL,
    bank_account_number VARCHAR(100) NULL,
    bank_ifsc VARCHAR(20) NULL,
    vendor_type VARCHAR(100) NULL,
    status ENUM('active','inactive','blacklisted') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_vendor_code (organization_id, vendor_code),
    CONSTRAINT fk_vendor_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE purchase_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    request_reference VARCHAR(50) NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    request_date DATE NOT NULL,
    required_date DATE NULL,
    purpose TEXT NULL,
    status ENUM('draft','submitted','approved','rejected','converted','cancelled') NOT NULL DEFAULT 'draft',
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_purchase_request_ref (organization_id, request_reference),
    CONSTRAINT fk_pr_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE purchase_request_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_request_id BIGINT UNSIGNED NOT NULL,
    inventory_item_id BIGINT UNSIGNED NULL,
    item_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    quantity DECIMAL(14,2) NOT NULL,
    estimated_unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    estimated_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_pri_request FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_pri_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL
);

CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    purchase_request_id BIGINT UNSIGNED NULL,
    vendor_id BIGINT UNSIGNED NOT NULL,
    po_number VARCHAR(50) NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft','sent','confirmed','partially_received','received','cancelled') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_po_number (organization_id, po_number),
    CONSTRAINT fk_po_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_po_request FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_po_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE RESTRICT
);

CREATE TABLE purchase_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    inventory_item_id BIGINT UNSIGNED NULL,
    item_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    ordered_quantity DECIMAL(14,2) NOT NULL,
    received_quantity DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_poi_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL
);

CREATE TABLE goods_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    receipt_number VARCHAR(50) NOT NULL,
    receipt_date DATE NOT NULL,
    received_by BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_goods_receipt (organization_id, receipt_number),
    CONSTRAINT fk_gr_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_gr_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE RESTRICT
);

CREATE TABLE vendor_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    vendor_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NULL,
    invoice_number VARCHAR(100) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('unpaid','partially_paid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
    file_path VARCHAR(500) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vendor_invoice (organization_id, vendor_id, invoice_number),
    CONSTRAINT fk_vi_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_vi_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE RESTRICT,
    CONSTRAINT fk_vi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL
);

-- ============================================================
-- 14. EVENTS / SCHOOL ACTIVITIES / TRANSPORT / ACCOMMODATION
-- ============================================================

CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    event_reference VARCHAR(50) NOT NULL,
    name VARCHAR(200) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    venue_id BIGINT UNSIGNED NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    organizer_employee_id BIGINT UNSIGNED NULL,
    status ENUM('draft','planned','ongoing','completed','cancelled') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_event_reference (organization_id, event_reference),
    CONSTRAINT fk_event_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
    CONSTRAINT fk_event_organizer FOREIGN KEY (organizer_employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE TABLE school_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NULL,
    school_name VARCHAR(200) NOT NULL,
    school_code VARCHAR(50) NULL,
    sport_id BIGINT UNSIGNED NULL,
    activity_name VARCHAR(200) NOT NULL,
    activity_date DATE NOT NULL,
    venue_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    participant_count INT UNSIGNED NULL,
    status ENUM('planned','ongoing','completed','cancelled') NOT NULL DEFAULT 'planned',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_school_activity_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_school_activity_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_school_activity_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE SET NULL,
    CONSTRAINT fk_school_activity_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL
);

CREATE TABLE event_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    participant_type ENUM('athlete','employee','coach','team') NOT NULL,
    athlete_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    coach_id BIGINT UNSIGNED NULL,
    team_id BIGINT UNSIGNED NULL,
    role_name VARCHAR(100) NULL,
    status ENUM('invited','confirmed','attended','cancelled') NOT NULL DEFAULT 'invited',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ep_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_ep_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_ep_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_ep_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL,
    CONSTRAINT fk_ep_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);

CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    vehicle_number VARCHAR(50) NOT NULL,
    vehicle_type VARCHAR(100) NOT NULL,
    make VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    year SMALLINT UNSIGNED NULL,
    capacity INT UNSIGNED NULL,
    driver_employee_id BIGINT UNSIGNED NULL,
    insurance_expiry_date DATE NULL,
    registration_expiry_date DATE NULL,
    status ENUM('available','assigned','maintenance','inactive') NOT NULL DEFAULT 'available',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_vehicle_number (organization_id, vehicle_number),
    CONSTRAINT fk_vehicle_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_driver FOREIGN KEY (driver_employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE TABLE transport_trips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    trip_reference VARCHAR(50) NOT NULL,
    trip_date DATE NOT NULL,
    departure_time TIME NULL,
    return_time TIME NULL,
    origin VARCHAR(255) NULL,
    destination VARCHAR(255) NULL,
    purpose VARCHAR(255) NULL,
    driver_employee_id BIGINT UNSIGNED NULL,
    status ENUM('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_transport_trip (organization_id, trip_reference),
    CONSTRAINT fk_trip_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_trip_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_trip_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    CONSTRAINT fk_trip_driver FOREIGN KEY (driver_employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

CREATE TABLE transport_passengers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transport_trip_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    coach_id BIGINT UNSIGNED NULL,
    passenger_name VARCHAR(150) NULL,
    pickup_location VARCHAR(255) NULL,
    drop_location VARCHAR(255) NULL,
    status ENUM('planned','boarded','dropped','cancelled') NOT NULL DEFAULT 'planned',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tp_trip FOREIGN KEY (transport_trip_id) REFERENCES transport_trips(id) ON DELETE CASCADE,
    CONSTRAINT fk_tp_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_tp_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_tp_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL
);

CREATE TABLE accommodations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    accommodation_type VARCHAR(100) NULL,
    contact_person VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    total_rooms INT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_accommodation_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE accommodation_rooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accommodation_id BIGINT UNSIGNED NOT NULL,
    room_number VARCHAR(50) NOT NULL,
    room_type VARCHAR(100) NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 1,
    floor_number VARCHAR(30) NULL,
    status ENUM('available','occupied','maintenance','inactive') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_accommodation_room (accommodation_id, room_number),
    CONSTRAINT fk_room_accommodation FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE
);

CREATE TABLE accommodation_allocations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accommodation_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NULL,
    athlete_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    coach_id BIGINT UNSIGNED NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NULL,
    status ENUM('reserved','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'reserved',
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_aa_accommodation FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE,
    CONSTRAINT fk_aa_room FOREIGN KEY (room_id) REFERENCES accommodation_rooms(id) ON DELETE RESTRICT,
    CONSTRAINT fk_aa_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_aa_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL,
    CONSTRAINT fk_aa_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_aa_coach FOREIGN KEY (coach_id) REFERENCES coach_profiles(id) ON DELETE SET NULL
);

-- ============================================================
-- 15. FINANCE / EXPENSES / PAYMENTS / BUDGETS
-- ============================================================

CREATE TABLE finance_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    category_type ENUM('income','expense','both') NOT NULL DEFAULT 'expense',
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_finance_category (organization_id, name),
    CONSTRAINT fk_finance_category_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE budgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    budget_name VARCHAR(150) NOT NULL,
    financial_year VARCHAR(20) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_budget DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft','active','closed','cancelled') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_budget_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE budget_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_id BIGINT UNSIGNED NOT NULL,
    finance_category_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    allocated_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    description VARCHAR(255) NULL,
    CONSTRAINT fk_budget_item_budget FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    CONSTRAINT fk_budget_item_category FOREIGN KEY (finance_category_id) REFERENCES finance_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_budget_item_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    expense_reference VARCHAR(50) NOT NULL,
    finance_category_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    vendor_id BIGINT UNSIGNED NULL,
    event_id BIGINT UNSIGNED NULL,
    tournament_id BIGINT UNSIGNED NULL,
    expense_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL,
    payment_status ENUM('pending','approved','paid','rejected','cancelled') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) NULL,
    payment_reference VARCHAR(100) NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    receipt_path VARCHAR(500) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_expense_reference (organization_id, expense_reference),
    CONSTRAINT fk_expense_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_expense_category FOREIGN KEY (finance_category_id) REFERENCES finance_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_expense_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_expense_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
    CONSTRAINT fk_expense_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_expense_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE SET NULL
);

CREATE TABLE income_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    income_reference VARCHAR(50) NOT NULL,
    finance_category_id BIGINT UNSIGNED NULL,
    income_date DATE NOT NULL,
    source_name VARCHAR(200) NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    payment_method VARCHAR(50) NULL,
    payment_reference VARCHAR(100) NULL,
    receipt_path VARCHAR(500) NULL,
    status ENUM('pending','received','cancelled') NOT NULL DEFAULT 'received',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_income_reference (organization_id, income_reference),
    CONSTRAINT fk_income_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_income_category FOREIGN KEY (finance_category_id) REFERENCES finance_categories(id) ON DELETE SET NULL
);

CREATE TABLE finance_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    payment_reference VARCHAR(50) NOT NULL,
    payment_type ENUM('expense','vendor_invoice','payroll','other') NOT NULL,
    expense_id BIGINT UNSIGNED NULL,
    vendor_invoice_id BIGINT UNSIGNED NULL,
    payroll_id BIGINT UNSIGNED NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    payment_method VARCHAR(50) NULL,
    transaction_reference VARCHAR(150) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_finance_payment_reference (organization_id, payment_reference),
    CONSTRAINT fk_fp_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_fp_expense FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE SET NULL,
    CONSTRAINT fk_fp_vendor_invoice FOREIGN KEY (vendor_invoice_id) REFERENCES vendor_invoices(id) ON DELETE SET NULL,
    CONSTRAINT fk_fp_payroll FOREIGN KEY (payroll_id) REFERENCES payroll(id) ON DELETE SET NULL
);

-- ============================================================
-- 16. NOTIFICATIONS
-- ============================================================

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(80) NULL,
    reference_type VARCHAR(80) NULL,
    reference_id BIGINT UNSIGNED NULL,
    channel ENUM('in_app','push','email') NOT NULL DEFAULT 'in_app',
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_user (user_id, is_read, created_at),
    CONSTRAINT fk_notification_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 16A. CROSS-MODULE FOREIGN KEYS
-- These are added after all referenced tables have been created.
-- ============================================================

ALTER TABLE venue_bookings
    ADD CONSTRAINT fk_booking_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_booking_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE SET NULL;

ALTER TABLE venue_maintenance
    ADD CONSTRAINT fk_vm_vendor FOREIGN KEY (assigned_vendor_id) REFERENCES vendors(id) ON DELETE SET NULL;

ALTER TABLE athlete_performance
    ADD CONSTRAINT fk_perf_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE SET NULL;

-- ============================================================
-- 17. AUDIT LOGS
-- ============================================================

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    module VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NULL,
    record_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_org_date (organization_id, created_at),
    INDEX idx_audit_user_date (user_id, created_at),
    INDEX idx_audit_record (table_name, record_id),
    CONSTRAINT fk_audit_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 18. SYSTEM SETTINGS
-- ============================================================

CREATE TABLE organization_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    setting_key VARCHAR(150) NOT NULL,
    setting_value TEXT NULL,
    setting_type ENUM('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_org_setting (organization_id, setting_key),
    CONSTRAINT fk_org_setting_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- ============================================================
-- 19. FOREIGN KEYS THAT DEPEND ON TABLES CREATED LATER
-- ============================================================

ALTER TABLE organizations
    ADD CONSTRAINT fk_org_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_org_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE organization_users
    ADD CONSTRAINT fk_ou_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ou_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ou_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_ou_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ou_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ou_athlete FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE SET NULL;

-- ============================================================
-- 20. SEED ROLES
-- ============================================================

INSERT INTO roles (name, description) VALUES
('Super Admin', 'Software provider administrator with global access to all organisations'),
('Sports Administrator', 'Organisation-level administrator for sports operations'),
('HR & Finance', 'Organisation-level human resources and finance administrator'),
('Coach', 'Coach with access to assigned teams, training and performance'),
('Athlete', 'Athlete with access to own profile, team, training, fixtures and performance'),
('Venue & Tournament Manager', 'Organisation-level venue and tournament manager'),
('Inventory Manager', 'Organisation-level inventory, equipment, vendor and purchase manager');

-- ============================================================
-- 21. SEED TOURNAMENT LEVELS
-- ============================================================

INSERT INTO tournament_levels (name, description) VALUES
('School', 'School-level competition'),
('District', 'District-level competition'),
('State', 'State-level competition'),
('National', 'National-level competition'),
('International', 'International-level competition');

-- ============================================================
-- 22. SEED TOURNAMENT FORMATS
-- ============================================================

INSERT INTO tournament_formats (name, description) VALUES
('Knockout', 'Single or multi-round elimination'),
('League', 'League format'),
('Round Robin', 'Every participating team plays the other teams'),
('Group + Knockout', 'Group stage followed by knockout rounds');

-- ============================================================
-- 23. SEED PERMISSIONS
-- ============================================================
-- Permission rows are intentionally explicit so role_permissions
-- can be configured without changing the database structure.

INSERT INTO permissions (name, module, action, description) VALUES
('organization.view','organization','view','View organisations'),
('organization.create','organization','create','Create organisations'),
('organization.update','organization','update','Update organisations'),
('organization.suspend','organization','suspend','Suspend organisation access'),

('user.view','user','view','View users'),
('user.create','user','create','Create users'),
('user.update','user','update','Update users'),
('user.deactivate','user','deactivate','Deactivate users'),

('athlete.view','athlete','view','View athletes'),
('athlete.create','athlete','create','Create athletes'),
('athlete.update','athlete','update','Update athletes'),
('athlete.delete','athlete','delete','Soft delete athletes'),
('athlete.medical.view','athlete_medical','view','View athlete medical records'),
('athlete.medical.manage','athlete_medical','manage','Manage athlete medical records'),
('athlete.performance.view','athlete_performance','view','View athlete performance'),
('athlete.performance.manage','athlete_performance','manage','Manage athlete performance'),

('coach.view','coach','view','View coaches'),
('coach.create','coach','create','Create coaches'),
('coach.update','coach','update','Update coaches'),

('team.view','team','view','View teams'),
('team.create','team','create','Create teams'),
('team.update','team','update','Update teams'),
('team.members.manage','team','members_manage','Manage team members'),
('team.coaches.manage','team','coaches_manage','Manage team coaches'),

('training.view','training','view','View training'),
('training.create','training','create','Create training'),
('training.update','training','update','Update training'),
('training.attendance.manage','training','attendance_manage','Manage training attendance'),

('tournament.view','tournament','view','View tournaments'),
('tournament.create','tournament','create','Create tournaments'),
('tournament.update','tournament','update','Update tournaments'),
('fixture.manage','fixture','manage','Manage fixtures'),
('match.manage','match','manage','Manage matches and results'),

('venue.view','venue','view','View venues'),
('venue.create','venue','create','Create venues'),
('venue.update','venue','update','Update venues'),
('venue.booking.manage','venue_booking','manage','Manage venue bookings'),
('venue.maintenance.manage','venue_maintenance','manage','Manage venue maintenance'),
('housekeeping.manage','housekeeping','manage','Manage housekeeping'),

('employee.view','employee','view','View employees'),
('employee.create','employee','create','Create employees'),
('employee.update','employee','update','Update employees'),
('leave.view','leave','view','View leave'),
('leave.create','leave','create','Create leave requests'),
('leave.approve','leave','approve','Approve or reject leave'),
('payroll.view','payroll','view','View payroll'),
('payroll.manage','payroll','manage','Manage payroll'),

('inventory.view','inventory','view','View inventory'),
('inventory.manage','inventory','manage','Manage inventory'),
('equipment.manage','equipment','manage','Manage equipment'),
('vendor.manage','vendor','manage','Manage vendors'),
('purchase.manage','purchase','manage','Manage purchases'),

('event.view','event','view','View events'),
('event.manage','event','manage','Manage events'),
('transport.manage','transport','manage','Manage transport'),
('accommodation.manage','accommodation','manage','Manage accommodation'),

('finance.view','finance','view','View finance'),
('finance.manage','finance','manage','Manage finance'),
('report.view','report','view','View reports'),
('notification.view','notification','view','View notifications'),
('notification.manage','notification','manage','Manage notifications'),
('settings.manage','settings','manage','Manage organisation settings'),
('audit.view','audit','view','View audit logs');

-- ============================================================
-- 24. IMPORTANT BUSINESS-RULE NOTES
-- ============================================================
-- The following rules should be enforced in Laravel services/policies:
--
-- A. Super Admin creates organisations and their initial Sports Administrator.
-- B. Sports Administrator users cannot be created by other organisation roles.
-- C. A user belongs to one organisation role per organisation in organization_users.
-- D. An athlete has one current sport; history is retained in athlete_sport_history.
-- E. An athlete can have many historical/current team assignments.
-- F. A team can have one primary/head coach by default, with additional coaches allowed.
-- G. Venue booking overlap must be checked in application logic/transaction.
-- H. Tournament home_team_id and away_team_id must be different.
-- I. Only Venue & Tournament Manager and Sports Administrator can manage tournaments.
-- J. Only Sports Administrator and Venue & Tournament Manager can create/manage venue bookings.
-- K. HR & Finance approves leave and manages payroll.
-- L. Medical records require permission checks and must not be exposed to ordinary roles.
-- M. Inventory quantity must be updated through stock_transactions, not arbitrary direct edits.
-- N. Payroll is organisation-scoped and excludes PF/ESI/professional-tax statutory fields by design.
-- O. All important create/update/delete/approval/payment actions should write audit_logs.
-- P. Mobile clients must use Laravel REST APIs; they must never connect directly to MySQL.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
