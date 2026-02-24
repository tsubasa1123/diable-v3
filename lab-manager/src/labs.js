// src/labs.js — Catalogue des labs disponibles
// Ajouter un lab ici suffit pour l'intégrer dans la plateforme

const LABS = {
    'xss-lab': {
        id: 'xss-lab',
        title: 'Cross-Site Scripting (XSS)',
        portSuffix: 1,           // 8[user_id][suffix] → ex: user 5 = port 8051
        composeDir: 'xss-lab',       // sous-dossier dans LABS_DIR
        exercises: 4,
    },
    'xpath-lab': {
        id: 'xpath-lab',
        title: 'XPath Injection',
        portSuffix: 2,
        composeDir: 'xpath-lab',
        exercises: 3,
    },
    'sqli-lab': {
        id: 'sqli-lab',
        title: 'SQL Injection',
        portSuffix: 3,
        composeDir: 'sqli-lab',
        exercises: 4,
    },
    'phishing': {
        id: 'phishing',
        title: 'Phishing',
        portSuffix: 4,
        composeDir: 'phishing',
        exercises: 3,
    },
    'nosql-injection-lab': {
        id: 'nosql-injection-lab',
        title: 'Nosql injection',
        portSuffix: 5,
        composeDir: 'nosql-injection-lab',
        exercises: 3,
    },
    'path-traversal-lab': {
        id: 'path-traversal-lab',
        title: 'Path traversal',
        portSuffix: 6,
        composeDir: 'path-traversal-lab',
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
