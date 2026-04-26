-- ============================================================
--  Grama Niladhari – Resident Database Backup
-- ============================================================

-- ── Table: residents ────────────────────────────────────────
DROP TABLE IF EXISTS residents;

CREATE TABLE residents (
  id              INT           AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(100)  NOT NULL,
  dob             DATE          NOT NULL,
  nic             VARCHAR(12)   UNIQUE NOT NULL,
  address         TEXT          NOT NULL,
  phone           VARCHAR(15)   NOT NULL,
  email           VARCHAR(100)  NOT NULL,
  occupation      VARCHAR(50)   NULL,
  gender          ENUM('Male','Female','Other') NOT NULL,
  registered_date TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 10 Sample Residents ─────────────────────────────────────
INSERT INTO residents
  (full_name, dob, nic, address, phone, email, occupation, gender)
VALUES
  ('Kamal Perera',       '1985-03-12', '850312345V',  '42, Kandy Road, Nugegoda, Colombo',          '0771234567', 'kamal.perera@email.com',    'Engineer',         'Male'),
  ('Niluka Fernando',    '1990-07-25', '906725689V',  '15, Galle Road, Dehiwala, Colombo',           '0712345678', 'niluka.fernando@email.com', 'Teacher',          'Female'),
  ('Suresh Rajapaksa',   '1978-11-08', '781108234V',  '78, High Level Road, Maharagama, Colombo',    '0723456789', 'suresh.raja@email.com',     'Businessman',      'Male'),
  ('Priya Wickramasinghe','1995-05-14','956514789V',  '23, Station Road, Ratmalana, Colombo',        '0734567890', 'priya.wick@email.com',      'Nurse',            'Female'),
  ('Amal Jayasinghe',    '1982-09-30', '820930567V',  '56, Baseline Road, Borella, Colombo 8',       '0745678901', 'amal.jaya@email.com',       'Accountant',       'Male'),
  ('Dilani Silva',       '1998-02-18', '980218456V',  '11, Temple Road, Kotte, Sri Jayawardenepura', '0756789012', 'dilani.silva@email.com',    'Student',          'Female'),
  ('Ranjith Kumara',     '1970-06-22', '700622123V',  '88, Highlevel Road, Pannipitiya, Colombo',    '0767890123', 'ranjith.kumara@email.com',  'Farmer',           'Male'),
  ('Samanthi De Silva',  '1993-12-05', '936505678V',  '34, Dutugemunu Street, Kohuwala, Colombo',    '0778901234', 'samanthi.des@email.com',    'Software Engineer','Female'),
  ('Buddhika Bandara',   '1988-04-17', '880417890V',  '67, School Lane, Piliyandala, Colombo',       '0789012345', 'buddhika.ban@email.com',    'Driver',           'Male'),
  ('Thilini Madushanka', '2000-08-29', '200029100V',  '5, New Road, Kottawa, Colombo',               '0790123456', 'thilini.mad@email.com',     'Beautician',       'Female');