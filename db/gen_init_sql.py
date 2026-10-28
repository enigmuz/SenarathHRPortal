#!/usr/bin/env python3
"""Generator for init.sql — synthetic HR data for the vulnerable lab portal.
All people, NICs, accounts, salaries are FAKE. Run: python3 gen_init_sql.py > init.sql
"""
import random
random.seed(1337)

first_m = ["Nuwan","Kasun","Chaminda","Dinesh","Suresh","Ruwan","Tharindu","Isuru",
           "Lahiru","Pasindu","Sahan","Gayan","Roshan","Nimal","Buddhika","Thilina"]
first_f = ["Dilini","Sachini","Harshani","Tharushi","Nethmi","Ishara","Madhavi",
           "Amaya","Kavindi","Shalini","Hiruni","Sewwandi","Nadeesha","Ruvini"]
last = ["Perera","Fernando","Silva","Jayawardena","Bandara","Wickramasinghe",
        "Gunawardena","Dissanayake","Rajapaksa","Senanayake","Herath","Ekanayake",
        "Weerasinghe","Abeywardena","Kumara","Rathnayake","Senarath","Madushanka"]

departments = [
    ("Retail Banking","Branch Officer"),("Corporate Banking","Relationship Manager"),
    ("Finance","Accountant"),("Treasury","Dealer"),("IT","Systems Engineer"),
    ("IT","Security Analyst"),("Human Resources","HR Officer"),("Operations","Operations Executive"),
    ("Risk & Compliance","Compliance Officer"),("Marketing","Marketing Executive"),
    ("Legal","Legal Officer"),("Internal Audit","Auditor"),
]

# Weak / guessable passwords on purpose (lab). Stored in PLAINTEXT.
weak_pw = ["password123","welcome1","123456","senarath","bank@123","changeme",
           "qwerty123","letmein","abc123","summer2025","Password1","admin123"]

def nic():
    # Synthetic Sri Lankan-style NIC (fake, not tied to any real person)
    return f"{random.randint(85,99)}{random.randint(1000,9999)}{random.randint(1000,9999)}V"

def phone():
    return f"07{random.randint(0,9)}{random.randint(1000000,9999999)}"

def acc():
    return f"{random.randint(1000,9999)}-{random.randint(100000,999999)}-{random.randint(0,9)}"

rows = []
# Fixed, well-known accounts first (handy targets for the exercise)
seed_users = [
    # username, password, full_name, gender, dept, pos, role
    ("admin","admin123","Ranil Senarath","M","IT","System Administrator","admin"),
    ("hrmanager","welcome1","Dilini Perera","F","Human Resources","HR Manager","manager"),
    ("auditor","audit@123","Suresh Fernando","M","Internal Audit","Chief Auditor","manager"),
]
used = set(u[0] for u in seed_users)

all_users = list(seed_users)
# Generate the rest up to 30 total
while len(all_users) < 30:
    gender = random.choice(["M","F"])
    fn = random.choice(first_m if gender=="M" else first_f)
    ln = random.choice(last)
    base = (fn[0] + ln).lower()
    uname = base
    n = 1
    while uname in used:
        n += 1
        uname = f"{base}{n}"
    used.add(uname)
    dept, pos = random.choice(departments)
    pw = random.choice(weak_pw)
    role = "manager" if random.random() < 0.15 else "employee"
    all_users.append((uname, pw, f"{fn} {ln}", gender, dept, pos, role))

def esc(s): return s.replace("'", "''")

print("-- =====================================================================")
print("--  Senarath Bank HR Management Portal — schema + synthetic seed data")
print("--  INTENTIONALLY INSECURE LAB DATA. All records are fictional.")
print("-- =====================================================================")
print("SET NAMES utf8mb4;")
print("SET FOREIGN_KEY_CHECKS=0;")
print()
print("DROP TABLE IF EXISTS activities;")
print("DROP TABLE IF EXISTS payroll;")
print("DROP TABLE IF EXISTS leave_requests;")
print("DROP TABLE IF EXISTS users;")
print()
print("""CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL,
  password      VARCHAR(255) NOT NULL,         -- PLAINTEXT on purpose (lab)
  full_name     VARCHAR(120) NOT NULL,
  gender        CHAR(1),
  dob           DATE,
  nic           VARCHAR(20),
  email         VARCHAR(120),
  phone         VARCHAR(20),
  address       VARCHAR(200),
  department    VARCHAR(80),
  position      VARCHAR(80),
  role          VARCHAR(20)  DEFAULT 'employee',-- employee | manager | admin
  bank_account  VARCHAR(30),
  date_joined   DATE,
  status        VARCHAR(20)  DEFAULT 'active',
  emergency_contact VARCHAR(120)
) ENGINE=InnoDB;""")
print()
print("""CREATE TABLE payroll (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  pay_month   VARCHAR(10),
  basic       DECIMAL(12,2),
  allowances  DECIMAL(12,2),
  deductions  DECIMAL(12,2),
  epf         DECIMAL(12,2),
  etf         DECIMAL(12,2),
  net_salary  DECIMAL(12,2),
  pay_date    DATE
) ENGINE=InnoDB;""")
print()
print("""CREATE TABLE activities (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  activity    VARCHAR(200),
  ip_address  VARCHAR(45),
  created_at  DATETIME
) ENGINE=InnoDB;""")
print()
print("""CREATE TABLE leave_requests (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  leave_type  VARCHAR(40),
  from_date   DATE,
  to_date     DATE,
  reason      VARCHAR(200),
  status      VARCHAR(20) DEFAULT 'pending'
) ENGINE=InnoDB;""")
print()

print("INSERT INTO users (username,password,full_name,gender,dob,nic,email,phone,address,department,position,role,bank_account,date_joined,status,emergency_contact) VALUES")
vals = []
cities = ["Colombo","Kandy","Galle","Negombo","Dickwella","Matara","Kurunegala","Jaffna","Gampaha","Nugegoda"]
streets = ["Galle Rd","Temple Rd","Lake Rd","Main St","Station Rd","Hospital Rd","School Ln","Park Ave"]
for i,(uname,pw,fname,gender,dept,pos,role) in enumerate(all_users, start=1):
    y = random.randint(1978,1998)
    dob = f"{y}-{random.randint(1,12):02d}-{random.randint(1,28):02d}"
    email = f"{uname}@senarathbank.lk"
    addr = f"{random.randint(1,350)}, {random.choice(streets)}, {random.choice(cities)}"
    joined = f"{random.randint(2012,2024)}-{random.randint(1,12):02d}-{random.randint(1,28):02d}"
    emerg = f"{random.choice(first_m+first_f)} {random.choice(last)} ({phone()})"
    vals.append(
        f"('{esc(uname)}','{esc(pw)}','{esc(fname)}','{gender}','{dob}','{nic()}',"
        f"'{esc(email)}','{phone()}','{esc(addr)}','{esc(dept)}','{esc(pos)}','{role}',"
        f"'{acc()}','{joined}','active','{esc(emerg)}')"
    )
print(",\n".join(vals) + ";")
print()

# Payroll: last 3 months for each user
months = ["2025-08","2025-09","2025-10"]
print("INSERT INTO payroll (user_id,pay_month,basic,allowances,deductions,epf,etf,net_salary,pay_date) VALUES")
pvals = []
for uid,(uname,pw,fname,gender,dept,pos,role) in enumerate(all_users, start=1):
    if role=="admin": basic=325000
    elif role=="manager": basic=random.choice([240000,275000,300000])
    else: basic=random.choice([85000,95000,110000,125000,140000,165000])
    for m in months:
        allow = round(basic*random.uniform(0.15,0.30),2)
        epf = round(basic*0.08,2)
        etf = round(basic*0.03,2)
        ded = round(epf+random.uniform(2000,8000),2)
        net = round(basic+allow-ded,2)
        pay_date = m + "-28"
        pvals.append(f"({uid},'{m}',{basic:.2f},{allow:.2f},{ded:.2f},{epf:.2f},{etf:.2f},{net:.2f},'{pay_date}')")
print(",\n".join(pvals) + ";")
print()

# Activities
acts = ["Logged in","Viewed payslip","Downloaded payslip","Updated profile",
        "Submitted leave request","Viewed staff directory","Changed password",
        "Approved leave request","Exported report","Viewed dashboard"]
print("INSERT INTO activities (user_id,activity,ip_address,created_at) VALUES")
avals = []
for uid,_ in enumerate(all_users, start=1):
    for _ in range(random.randint(3,6)):
        a = random.choice(acts)
        ip = f"10.10.{random.randint(0,5)}.{random.randint(2,254)}"
        ts = f"2025-10-{random.randint(1,28):02d} {random.randint(8,18):02d}:{random.randint(0,59):02d}:{random.randint(0,59):02d}"
        avals.append(f"({uid},'{esc(a)}','{ip}','{ts}')")
print(",\n".join(avals) + ";")
print()

# Leave requests for ~half the users
ltypes = ["Annual","Casual","Medical","Short Leave","No-Pay"]
statuses = ["pending","approved","rejected"]
print("INSERT INTO leave_requests (user_id,leave_type,from_date,to_date,reason,status) VALUES")
lvals = []
for uid,_ in enumerate(all_users, start=1):
    if random.random() < 0.55:
        for _ in range(random.randint(1,2)):
            lt = random.choice(ltypes)
            fd_d = random.randint(1,20)
            fd = f"2025-11-{fd_d:02d}"
            td = f"2025-11-{fd_d+random.randint(0,4):02d}"
            reason = random.choice(["Family function","Not feeling well","Personal matter",
                                    "Travel","Medical appointment","Festival"])
            lvals.append(f"({uid},'{lt}','{fd}','{td}','{esc(reason)}','{random.choice(statuses)}')")
print(",\n".join(lvals) + ";")
print()
print("SET FOREIGN_KEY_CHECKS=1;")
