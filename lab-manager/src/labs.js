// src/labs.js — Catalogue des labs disponibles
// Ajouter un lab ici suffit pour l'intégrer dans la plateforme

const LABS = {
    'app-cve-2025-68613': {
        id: 'app-cve-2025-68613',
        title: 'n8n Workflow Automation',
        portSuffix: 1,
        composeDir: 'app-cve-2025-68613',
        exercises: 3,
        compose: true,   // ← docker compose car multi-conteneurs
        internalPort: 5678
    },
    'app-idor': {
        id: 'app-idor',
        title: 'App Idor',
        portSuffix: 2,
        composeDir: 'app-idor',
        exercises: 4,
        internalPort: 5000
    },
    'app-mfa-bypass': {
        id: 'app-mfa-bypass',
        title: 'MFA Bypass',
        portSuffix: 3,
        composeDir: 'app-mfa-bypass',
        internalPort: 5000,
        tty: true,   // ← indique que le conteneur a besoin d'un terminal
    },
    'broken-auth-lab': {
        id: 'broken-auth-lab',
        title: 'Broken auth lab',
        portSuffix: 4,
        composeDir: 'broken-auth-lab',
        exercises: 4,
    },
    'csrf': {
        id: 'csrf',
        title: 'Csrf',
        portSuffix: 5,
        composeDir: 'csrf',
        exercises: 4,
    },
    'email_header_injection': {
        id: 'email_header_injection',
        title: 'Email header Injection',
        portSuffix: 6,
        composeDir: 'email_header_injection',
        internalPort: 5000,
        env: {
            FLASK_ENV: 'production',
            DB_PATH: '/data/events.db',
        },
        volume: '/data',   // ← chemin interne à monter
    },
    'file-upload-lab': {
        id: 'file-upload-lab',
        title: 'File upload lab',
        portSuffix: 7,           // 8[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'file-upload-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
    },
    'graphql-lab': {
        id: 'graphql-lab',
        title: 'Graphql Lab',
        portSuffix: 8,           // 8[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'graphql-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
        internalPort: 3000
    },
    'heartbleed-lab': {
        id: 'heartbleed-lab',
        title: 'Heartbleed (CVE-2014-0160)',
        portSuffix: 9,
        composeDir: 'heartbleed-lab',
        internalPort: 443,   // ← port interne 443 (HTTPS), pas 4443
        https: true,
    },
    'injection_sql_temporell': {
        id: 'injection_sql_temporell',
        title: 'SQL Injection - Time Based',
        portSuffix: 10,
        composeDir: 'injection_sql_temporell',
        internalPort: 5000,
        volume: '/tmp',       // ← persistance SQLite
        env: {
            FLASK_ENV: 'production',
            PYTHONUNBUFFERED: '1',
        },
    },
    'jwt-lab': {
        id: 'jwt-lab',
        title: 'Jwt lab',
        portSuffix: 11,           // 10[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'jwt-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
        internalPort: 3000
    },
    'log4shell-dockerlab-main': {
        id: 'log4shell-dockerlab-main',
        title: 'Log4Shell (CVE-2021-44228)',
        portSuffix: 12,
        composeDir: 'log4shell-dockerlab-main',
        exercises: 3,
        compose: true,   // http://IP:8071 un IP, serveur vulnerable
        internalPort: 5000,
        extraPorts: {
            vulnerable: 1,
        },
    },
    'mitm-attack-lab': {
        id: 'mitm-attack-lab',
        title: 'MITM Attack',
        portSuffix: 13,
        composeDir: 'mitm-attack-lab',
        compose: true,
        internalPort: 3000,
        extraPorts: {
            victim: 1,   // ← port + 1 = victim-server
        }, //http://IP:8071  → interface principale du lab http://IP:8072  → victim-server, // ← port + 1 = victim-server
    },
    'nosql-injection-lab': {
        id: 'nosql-injection-lab',
        title: 'NoSQL Injection Lab',
        portSuffix: 14,
        composeDir: 'nosql-injection-lab',
        compose: true,
        internalPort: 3000
    },
    'path-traversal-lab': {
        id: 'path-traversal-lab',
        title: 'Path traversal',
        portSuffix: 15,
        composeDir: 'path-traversal-lab',
        exercises: 3,
        internalPort: 5000
    },
    'phishing': {
        id: 'phishing',
        title: 'Phishing',
        portSuffix: 16,
        composeDir: 'phishing',
        internalPort: 5000,
        env: {
            FLASK_ENV: 'production'
        },
    },
    'shellshock': {
        id: 'shellshock',
        title: 'Shellshock (CVE-2014-6271)',
        portSuffix: 17,
        composeDir: 'shellshock',
        internalPort: 80,
        //image: 'diable/shellshock-lab:latest',
        env: {
            DEBUG_MODE: 'false',
            FLASK_ENV: 'production',
        },
    },
    'sqli-auth-lab': {
        id: 'sqli-auth-lab',
        title: 'SQL Injection - Auth Bypass',
        portSuffix: 18,
        composeDir: 'sqli-auth-lab',
        internalPort: 80,
        //image: 'diable/sqli-auth-lab:latest',
        env: {
            DEBUG_MODE: 'false',
            DB_PATH: '/var/www/html/data/lab.db',
            LAB_NAME: 'sqli-auth-lab',
        },
    },
    'sqli-error-based-lab': {
        id: 'sqli-error-based',
        title: 'SQL Injection - Error Based',
        portSuffix: 19,
        composeDir: 'sqli-error-based-lab',
        internalPort: 5000,
        //image: 'diable/sqli-error-based-lab:latest',
        volume: '/app/data',   // ← volume à persister
        env: {
            PORT: '5000',
            DB_PATH: '/app/data/shop.db',
            LAB_NAME: 'sqli-error-based-lab',
            LAB_FLAG: 'DIABLE{SQLI_ERROR_BASED_OK}',
        },
    },
    'sqli-lab': {
        id: 'sqli-lab',
        title: 'SQL Injection - lab',
        portSuffix: 20,
        composeDir: 'sqli-lab',
        internalPort: 80,
        //image: 'diable/sqli-comments-lab:latest',  // ← Docker Hub
        env: {
            DEBUG_MODE: 'false',
            DB_PATH: '/var/www/html/database.db',
        },
    },
    'ssrf': {
        id: 'ssrf',
        title: 'Ssrf',
        portSuffix: 21,
        composeDir: 'ssrf',
        exercises: 4,
    },
    'xpath-lab': {
        id: 'xpath-lab',
        title: 'XPath Injection',
        portSuffix: 22,
        composeDir: 'xpath-lab',
        internalPort: 80,
        //image: 'diable/xpath-injection-lab:latest',
        env: {
            DEBUG_MODE: 'false',
            DATA_PATH: '/var/www/html/data',
        },
    },
    'xss-lab': {
        id: 'xss-lab',
        title: 'Cross-Site Scripting (XSS)',
        portSuffix: 23,           // 8[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'xss-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
    },
    'xxe-lab': {
        id: 'xxe-lab',
        title: 'XXE Lab',
        portSuffix: 24,
        composeDir: 'xxe-lab',
        exercises: 3,
    },
    'api_scanning': {
        id: 'api_scanning',
        title: 'Broken Auth / IDOR / Exposed Admin',
        portSuffix: 25,
        composeDir: 'api_scanning',
        internalPort: 5000,
        env: {
            FLASK_ENV: 'production',
            SECRET_KEY: 'dev-secret-change-in-prod',
        },
    },
    'blind-sqli': {
        id: 'blind-sqli',
        title: 'Blind Sqli',
        portSuffix: 26,
        composeDir: 'blind-sqli',
        internalPort: 5000
    }
};

/**
 * Calcule le port d'un lab pour un utilisateur donné.
 * Convention : 8 + [user_id sur 2 chiffres] + [portSuffix sur 1 chiffre]
 * Ex : user_id=5, lab=xss (suffix=1) → 8051
 * Ex : user_id=12, lab=sqli (suffix=3) → 8123
 */

function getPort(userId, labId) {
    const lab = LABS[labId];
    if (!lab) throw new Error(`Lab inconnu : ${labId}`);

    const uid = parseInt(userId) % 3000;
    return 10000 + (uid * 20) + lab.portSuffix;
}

/**
 * Nom unique du conteneur Docker pour cet apprenant + lab.
 * Ex : lab-xss-user-42
 */
function getContainerName(userId, labId) {
    return `${labId}-u-${userId}`;
}

module.exports = { LABS, getPort, getContainerName };
