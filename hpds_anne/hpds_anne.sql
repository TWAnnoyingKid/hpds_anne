-- 急救訓練管理平台資料庫建立腳本
-- 適用於 MySQL 5.7

-- 建立資料庫
CREATE DATABASE IF NOT EXISTS hpds_anne 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE hpds_anne;

-- 建立 users 表（用戶資料）
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    name TEXT,  -- Base64 編碼的姓名
    password VARCHAR(255) NOT NULL,
    permission ENUM('student', 'nurse', 'snurse', 'teacher', 'ta') DEFAULT 'student',
    gender ENUM('Male', 'Female') DEFAULT 'Male',
    
    -- 個人基本資料
    birth_date DATE,
    graduate_year INT,
    education_level ENUM('1', '2', '3', '4') COMMENT '1=專科,2=大學,3=碩士,4=博士',
    marital_status ENUM('1', '2', '3', '4', '5', '6') COMMENT '1=未婚,2=同居,3=已婚,4=分居,5=離婚,6=喪偶',
    hire_date DATE,
    
    -- 工作相關
    department ENUM('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13') COMMENT '1=內科,2=外科,3=骨科,4=泌尿科,5=皮膚科,6=眼科,7=耳鼻喉科,8=婦產科,9=兒科,10=ICU,11=ER,12=OR,13=其他',
    department_other TEXT,  -- Base64 編碼的其他部門
    facility_type ENUM('1', '2', '3', '4') COMMENT '1=醫學中心,2=區域醫院,3=地區醫院,4=學校',
    
    -- 工作經驗
    other_hospital_experience ENUM('0', '1') DEFAULT '0',
    other_hospital_years INT DEFAULT 0,
    critical_care_experience ENUM('0', '1') DEFAULT '0',
    critical_care_years INT DEFAULT 0,
    
    -- 急救訓練
    bls_training_date DATE,
    acls_experience ENUM('0', '1') DEFAULT '0',
    acls_training_date DATE,
    rescue_count INT DEFAULT 0,
    last_rescue_date DATE,
    rescue_course_count INT DEFAULT 0,
    
    -- 其他
    case_record_date DATE,
    group_assignment ENUM('1', '2', '3', '4', '5', '6', '7') COMMENT '1-6=實驗組,7=對照組',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_student_id (student_id),
    INDEX idx_permission (permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 建立 score 表（成績資料）
CREATE TABLE IF NOT EXISTS score (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    score INT NOT NULL,
    incorrect_questions TEXT,  -- 錯題編號，用逗號分隔
    answer_time VARCHAR(10),   -- 答題時間 格式: mm:ss
    answer_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Q1 到 Q16 的答題結果 (1=答對, 0=答錯)
    q1 TINYINT(1) DEFAULT 1,
    q2 TINYINT(1) DEFAULT 1,
    q3 TINYINT(1) DEFAULT 1,
    q4 TINYINT(1) DEFAULT 1,
    q5 TINYINT(1) DEFAULT 1,
    q6 TINYINT(1) DEFAULT 1,
    q7 TINYINT(1) DEFAULT 1,
    q8 TINYINT(1) DEFAULT 1,
    q9 TINYINT(1) DEFAULT 1,
    q10 TINYINT(1) DEFAULT 1,
    q11 TINYINT(1) DEFAULT 1,
    q12 TINYINT(1) DEFAULT 1,
    q13 TINYINT(1) DEFAULT 1,
    q14 TINYINT(1) DEFAULT 1,
    q15 TINYINT(1) DEFAULT 1,
    q16 TINYINT(1) DEFAULT 1,
    
    INDEX idx_student_id (student_id),
    INDEX idx_score (score),
    INDEX idx_answer_date (answer_date),
    
    FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設管理員帳號 (使用手動計算的 Base64)
INSERT INTO users (student_id, name, password, permission, gender) VALUES 
('c111154240', '566h55CG5ZOh', '12345678', 'ta', 'Male'),
('965018', '5pWZ5bir', '965018', 'teacher', 'Female')
ON DUPLICATE KEY UPDATE id=id;

-- 插入測試學生帳號
INSERT INTO users (student_id, name, password, permission, gender) VALUES 
('S001', '5ris6Kmy5a246Jma77yR', '123456', 'student', 'Male'),
('S002', '5ris6Kmy5a246Jma77yS', '123456', 'student', 'Female')
ON DUPLICATE KEY UPDATE id=id;

-- 顯示建立結果
SHOW TABLES;
SELECT '資料庫建立完成！' as status;