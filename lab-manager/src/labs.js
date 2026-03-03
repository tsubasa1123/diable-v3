// src/labs.js — Catalogue des labs disponibles
// Ajouter un lab ici suffit pour l'intégrer dans la plateforme

const LABS = {
    'app-cve-2025-68613': {
        id: 'app-cve-2025-68613',
        title: 'n8n Workflow Automation',
        portSuffix: 7,
        composeDir: 'app-cve-2025-68613',
        exercises: 3,
        compose: true,   // ← docker compose car multi-conteneurs
    },
    'app-idor': {
        id: 'app-idor',
        title: 'App Idor',
        portSuffix: 2,
        composeDir: 'app-idor',
        exercises: 4,
    },
    'mfa-bypass': {
        id: 'mfa-bypass',
        title: 'MFA Bypass',
        portSuffix: 8,
        composeDir: 'mfa-bypass',
        exercises: 3,
        compose: false,
        internalPort: 5000,   // ← par défaut c'est 80, ici c'est 5000
    },
    'broken-auth-lab': {
        id: 'broken-auth-lab',
        title: 'Broken auth lab',
        portSuffix: 2,
        composeDir: 'broken-auth-lab',
        exercises: 4,
    },
    'email_header_injection': {
        id: 'email_header_injection',
        title: 'Email header Injection',
        portSuffix: 9,
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
        portSuffix: 1,           // 8[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'file-upload-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
    },
    'graphql-lab': {
        id: 'graphql-lab',
        title: 'Graphql Lab',
        portSuffix: 1,           // 8[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'graphql-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
    },
    'jwt-lab': {
        id: 'jwt-lab',
        title: 'Jwt lab',
        portSuffix: 1,           // 8[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'jwt-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
    },
    'log4shell': {
        id: 'log4shell',
        title: 'Log4Shell (CVE-2021-44228)',
        portSuffix: 6,
        composeDir: 'log4shell',
        exercises: 3,
        compose: true,   // http://IP:8071 un IP, serveur vulnerable
    },
    'mitm-attack-lab': {
        id: 'mitm-attack-lab',
        title: 'MITM Attack',
        portSuffix: 7,
        composeDir: 'mitm-attack-lab',
        compose: true,
        extraPorts: {
            victim: 1,   // ← port + 1 = victim-server
        }, //http://IP:8071  → interface principale du lab http://IP:8072  → victim-server, // ← port + 1 = victim-server
    },
    'nosql-injection': {
        id: 'nosql-injection',
        title: 'NoSQL Injection',
        portSuffix: 6,
        composeDir: 'nosql-injection',
        compose: true,
    },
    'path-traversal-lab': {
        id: 'path-traversal-lab',
        title: 'Path traversal',
        portSuffix: 6,
        composeDir: 'path-traversal-lab',
        exercises: 3,
    },
    'phishing': {
        id: 'phishing',
        title: '...',
        portSuffix: 10,
        composeDir: 'phishing',
        internalPort: 5000,
        env: {
            FLASK_ENV: 'production'
        },
    },
    'shellshock': {
        id: 'shellshock',
        title: 'Shellshock (CVE-2014-6271)',
        portSuffix: 5,
        composeDir: 'shellshock',
        internalPort: 80,
        image: 'diable/shellshock-lab:latest',
        env: {
            DEBUG_MODE: 'false',
            FLASK_ENV: 'production',
        },
    },
    'sqli-error-based': {
        id: 'sqli-error-based',
        title: 'SQL Injection - Error Based',
        portSuffix: 4,
        composeDir: 'sqli-error-based',
        internalPort: 5000,
        image: 'diable/sqli-error-based-lab:latest',
        volume: '/app/data',   // ← volume à persister
        env: {
            PORT: '5000',
            DB_PATH: '/app/data/shop.db',
            LAB_NAME: 'sqli-error-based',
            LAB_FLAG: 'DIABLE{SQLI_ERROR_BASED_OK}',
        },
    },
    'sqli-lab': {
        id: 'sqli-lab',
        title: 'SQL Injection - lab',
        portSuffix: 3,
        composeDir: 'sqli-lab',
        internalPort: 80,
        image: 'diable/sqli-comments-lab:latest',  // ← Docker Hub
        env: {
            DEBUG_MODE: 'false',
            DB_PATH: '/var/www/html/database.db',
        },
    },
    'xpath-lab': {
        id: 'xpath-lab',
        title: 'XPath Injection',
        portSuffix: 2,
        composeDir: 'xpath-lab',
        internalPort: 80,
        image: 'diable/xpath-injection-lab:latest',
        env: {
            DEBUG_MODE: 'false',
            DATA_PATH: '/var/www/html/data',
        },
    },
    'xss-lab': {
        id: 'xss-lab',
        title: 'Cross-Site Scripting (XSS)',
        portSuffix: 1,           // 8[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'xss-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
    },
    'xxe-lab': {
        id: 'xxe-lab',
        title: 'XXE Lab',
        portSuffix: 6,
        composeDir: 'xxe-lab',
        exercises: 3,
    },
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
    const uid = String(parseInt(userId) % 100).padStart(2, '0');
    return parseInt(`8${uid}${lab.portSuffix}`);
}

/**
 * Nom unique du conteneur Docker pour cet apprenant + lab.
 * Ex : lab-xss-user-42
 */
function getContainerName(userId, labId) {
    return `lab-${labId}-user-${userId}`;
}

module.exports = { LABS, getPort, getContainerName };
