import os
import copy

APP_NAME    = "Nexcorp Intranet Portal"
APP_VERSION = "3.4.1"
DEBUG       = True
SECRET_KEY  = os.environ.get("SECRET_KEY", "dev-secret-change-in-prod")

DB_HOST    = "postgres-primary.internal.nexcorp.com"
DB_NAME    = "nexcorp_prod"
DB_USER    = "nexcorp_app"
DB_PASS    = "Nx!Pr0d#2024Secure"
DB_PORT    = 5432
REDIS_URL  = "redis://cache.internal.nexcorp.com:6379"
SMTP_PASS  = "mail_relay_P@ssw0rd"
JWT_SECRET = "nxc-jwt-HS256-prod-k3y-d0-n0t-sh4re"
AWS_KEY_ID = "AKIAIOSFODNN7EXAMPLE"
AWS_SECRET = "wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"

USERS_DEFAULT = [
    {"id":1,"name":"Alice Chen",  "email":"alice.chen@nexcorp.com",  "password":"admin1234",  "role":"admin",   "dept":"Engineering","salary":145000,"ssn":"123-45-6789","manager_id":None,"flag":"DIABLE{id0r_is_4_real}"},
    {"id":2,"name":"John Smith",  "email":"john.smith@nexcorp.com",  "password":"password123","role":"employee","dept":"Marketing",  "salary":85000, "ssn":"987-65-4321","manager_id":1},
    {"id":3,"name":"Bob Kowalski","email":"bob.kowalski@nexcorp.com","password":"bob2024",    "role":"employee","dept":"Finance",    "salary":92000, "ssn":"555-12-9988","manager_id":1},
    {"id":4,"name":"Sarah Lin",   "email":"sarah.lin@nexcorp.com",   "password":"sarah456",   "role":"manager", "dept":"HR",         "salary":115000,"ssn":"321-00-8765","manager_id":None},
]

USERS = copy.deepcopy(USERS_DEFAULT)

ANNOUNCEMENTS = [
    {"id":1,"title":"Q3 All-Hands: July 18th","body":"Join us for the Q3 all-hands.","date":"2024-07-01","author":"CEO Office"},
    {"id":2,"title":"New VPN Policy","body":"All remote staff must use the new VPN client by Aug 1.","date":"2024-06-28","author":"IT Security"},
    {"id":3,"title":"Office Closure - July 4","body":"The office will be closed on July 4th.","date":"2024-06-25","author":"HR"},
]

TICKETS = [
    {"id":101,"user_id":2,"title":"Laptop screen flickering","status":"open",    "priority":"medium","created":"2024-07-02"},
    {"id":102,"user_id":2,"title":"VPN access request",       "status":"resolved","priority":"high",  "created":"2024-06-30"},
    {"id":103,"user_id":3,"title":"Email signature help",     "status":"open",    "priority":"low",   "created":"2024-07-01"},
]

AUDIT_LOG = [
    {"ts":"2024-07-03 09:14","user":"alice.chen",  "action":"LOGIN",        "detail":"Login from 10.0.1.12"},
    {"ts":"2024-07-03 09:18","user":"alice.chen",  "action":"CONFIG_EXPORT","detail":"Exported server config"},
    {"ts":"2024-07-03 10:02","user":"john.smith",  "action":"LOGIN",        "detail":"Login from 10.0.1.45"},
    {"ts":"2024-07-03 10:45","user":"admin_system","action":"USER_PROMOTED","detail":"bob.kowalski promoted"},
    {"ts":"2024-07-03 11:30","user":"sarah.lin",   "action":"SALARY_VIEW",  "detail":"Accessed payroll report"},
]

SERVER_CONFIG = {
    "env":         "production",
    "debug":       DEBUG,
    "db_host":     DB_HOST,
    "db_name":     DB_NAME,
    "db_user":     DB_USER,
    "db_pass":     DB_PASS,
    "redis_url":   REDIS_URL,
    "jwt_secret":  JWT_SECRET,
    "smtp_pass":   SMTP_PASS,
    "aws_key_id":  AWS_KEY_ID,
    "aws_secret":  AWS_SECRET,
    "admin_email": "sysadmin@nexcorp.com",
}