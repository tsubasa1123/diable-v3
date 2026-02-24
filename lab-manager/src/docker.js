// src/docker.js — Gestion des conteneurs Docker via dockerode
const Dockerode = require('dockerode');
const path      = require('path');
const { LABS, getPort, getContainerName } = require('./labs');

const docker   = new Dockerode({ socketPath: '/var/run/docker.sock' });
const LABS_DIR = process.env.LABS_DIR || '/opt/labs';
const NETWORK  = process.env.DOCKER_NETWORK || 'lab-network';
const BASE_HOST = process.env.BASE_HOST || 'localhost';

/**
 * Spawne un conteneur pour un apprenant.
 * Si un conteneur existe déjà pour cet user+lab, le retourne directement.
 */
async function spawnLab(userId, labId) {
    const lab           = LABS[labId];
    if (!lab) throw new Error(`Lab inconnu : ${labId}`);

    const containerName = getContainerName(userId, labId);
    const port          = getPort(userId, labId);
    const imageName     = `sec-lab-${labId}`;  // image buildée depuis les labs existants

    // Vérifier si le conteneur existe déjà
    const existing = await findContainer(containerName);
    if (existing) {
        // S'il est arrêté, le redémarrer
        const info = await existing.inspect();
        if (!info.State.Running) {
            await existing.start();
            console.log(`[Docker] Conteneur redémarré : ${containerName}`);
        } else {
            console.log(`[Docker] Conteneur déjà actif : ${containerName}`);
        }
        return { containerName, port, url: buildUrl(port) };
    }

    // Créer et démarrer le nouveau conteneur
    console.log(`[Docker] Création du conteneur ${containerName} sur le port ${port}...`);

    const container = await docker.createContainer({
        name:  containerName,
        Image: imageName,
        ExposedPorts: { '80/tcp': {} },
        HostConfig: {
            PortBindings: {
                '80/tcp': [{ HostPort: String(port) }]
            },
            NetworkMode: NETWORK,
            // Limites de ressources par conteneur
            Memory:    256 * 1024 * 1024,  // 256 Mo max
            NanoCpus:  500000000,           // 0.5 CPU max
            // Sécurité : lecture seule sauf /tmp
            ReadonlyRootfs: false,          // mettre true si les labs le permettent
        },
        Env: [
            `LAB_ID=${labId}`,
            `USER_ID=${userId}`,
        ],
    });

    await container.start();
    console.log(`[Docker] Conteneur démarré : ${containerName} → port ${port}`);

    return { containerName, port, url: buildUrl(port) };
}

/**
 * Détruit le conteneur d'un apprenant pour un lab donné.
 */
async function destroyLab(userId, labId) {
    const containerName = getContainerName(userId, labId);
    const container     = await findContainer(containerName);

    if (!container) {
        console.log(`[Docker] Conteneur introuvable (déjà détruit ?) : ${containerName}`);
        return false;
    }

    try {
        await container.stop({ t: 5 });   // 5 secondes pour s'arrêter proprement
    } catch (e) {
        // Peut échouer si déjà arrêté — pas grave
    }

    await container.remove({ force: true });
    console.log(`[Docker] Conteneur détruit : ${containerName}`);
    return true;
}

/**
 * Vérifie si un conteneur tourne pour cet apprenant + lab.
 */
async function getLabStatus(userId, labId) {
    const containerName = getContainerName(userId, labId);
    const container     = await findContainer(containerName);

    if (!container) return { running: false };

    const info = await container.inspect();
    return {
        running:       info.State.Running,
        containerName,
        port:          getPort(userId, labId),
        url:           buildUrl(getPort(userId, labId)),
        startedAt:     info.State.StartedAt,
    };
}

/**
 * Nettoie tous les conteneurs expirés (appelé par le cron).
 * Retourne la liste des conteneurs détruits.
 */
async function cleanupExpired(sessions) {
    const destroyed = [];
    for (const session of sessions) {
        try {
            await destroyLab(session.user_id, session.lab_id);
            destroyed.push(session.container_name);
        } catch (e) {
            console.error(`[Docker] Erreur cleanup ${session.container_name}:`, e.message);
        }
    }
    return destroyed;
}

/**
 * Construit l'image Docker d'un lab depuis son Dockerfile.
 * À appeler une fois lors du déploiement, pas à chaque spawn.
 */
async function buildLabImage(labId) {
    const lab       = LABS[labId];
    if (!lab) throw new Error(`Lab inconnu : ${labId}`);
    const labPath   = path.join(LABS_DIR, lab.composeDir);
    const imageName = `sec-lab-${labId}`;

    console.log(`[Docker] Build de l'image ${imageName} depuis ${labPath}...`);

    return new Promise((resolve, reject) => {
        docker.buildImage(
            { context: labPath, src: ['.'] },
            { t: imageName },
            (err, stream) => {
                if (err) return reject(err);
                docker.modem.followProgress(stream, (err, output) => {
                    if (err) return reject(err);
                    console.log(`[Docker] Image ${imageName} buildée avec succès`);
                    resolve(output);
                });
            }
        );
    });
}

// ── Helpers privés ────────────────────────────────────────────────────

async function findContainer(name) {
    try {
        const containers = await docker.listContainers({ all: true });
        const found = containers.find(c => c.Names.includes('/' + name));
        if (!found) return null;
        return docker.getContainer(found.Id);
    } catch (e) {
        return null;
    }
}

function buildUrl(port) {
    return `http://${BASE_HOST}:${port}`;
}

module.exports = { spawnLab, destroyLab, getLabStatus, cleanupExpired, buildLabImage };
