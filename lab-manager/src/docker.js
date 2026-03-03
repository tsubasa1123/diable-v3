// src/docker.js
const Dockerode = require('dockerode');
const path = require('path');
const { execSync } = require('child_process');
const fs = require('fs');
const { LABS, getPort, getContainerName } = require('./labs');

const docker = new Dockerode({ socketPath: '/var/run/docker.sock' });
const LABS_DIR = process.env.LABS_DIR || '../labs';
const NETWORK = process.env.DOCKER_NETWORK || 'lab-network';
const BASE_HOST = process.env.BASE_HOST || 'localhost';

// ── Spawn ─────────────────────────────────────────────────────────────

async function spawnLab(userId, labId) {
    const lab = LABS[labId];
    const labPath = path.join(LABS_DIR, lab.composeDir);
    const hasCompose = fs.existsSync(path.join(labPath, 'docker-compose.yml'));

    if (hasCompose) return spawnComposeLab(userId, labId, labPath);
    return spawnSingleLab(userId, labId);
}

async function spawnSingleLab(userId, labId) {
    const lab = LABS[labId];
    const containerName = getContainerName(userId, labId);
    const port = getPort(userId, labId);
    const imageName = lab.image || `sec-lab-${labId}`;

    const existing = await findContainer(containerName);
    if (existing) {
        const info = await existing.inspect();
        if (!info.State.Running) await existing.start();
        console.log(`[Docker] Conteneur réutilisé : ${containerName}`);
        return { containerName, port, url: buildUrl(port) };
    }

    console.log(`[Docker] Création : ${containerName} → port ${port}`);

    const container = await docker.createContainer({
        name: containerName,
        Image: imageName,
        Tty: lab.tty || false,
        OpenStdin: lab.tty || false,
        ExposedPorts: { [`${lab.internalPort || 80}/tcp`]: {} },
        HostConfig: {
            PortBindings: {
                [`${lab.internalPort || 80}/tcp`]: [{ HostPort: String(port) }]
            },
            NetworkMode: NETWORK,
            Memory: 256 * 1024 * 1024,
            NanoCpus: 500000000,
        },
        Env: [
            `LAB_ID=${labId}`,
            `USER_ID=${userId}`,
            ...Object.entries(lab.env || {}).map(([k, v]) => `${k}=${v}`),
        ],
        Binds: lab.volume
            ? [`lab-data-${labId}-user-${userId}:${lab.volume}`]  // volume Docker nommé par apprenant
            : [],
    });

    await container.start();
    console.log(`[Docker] Démarré : ${containerName} → port ${port}`);
    return { containerName, port, url: buildUrl(port) };
}

async function spawnComposeLab(userId, labId, labPath) {
    const lab = LABS[labId];
    const port = getPort(userId, labId);
    const containerName = getContainerName(userId, labId);

    console.log(`[Docker] Compose spawn : ${containerName} → port ${port}`);

    // Construire les variables d'environnement et les URLs dynamiquement
    const env = { ...process.env, USER_ID: String(userId), LAB_PORT: String(port) };
    const urls = { main: buildUrl(port) };

    if (lab.extraPorts) {
        for (const [name, offset] of Object.entries(lab.extraPorts)) {
            env[`${name.toUpperCase()}_PORT`] = String(port + offset);
            urls[name] = buildUrl(port + offset);
        }
    }

    execSync(
        `docker compose -p ${containerName} up -d --build`,
        { cwd: labPath, env }
    );

    return { containerName, port, url: buildUrl(port), urls };
}

// ── Destroy ───────────────────────────────────────────────────────────

async function destroyLab(userId, labId) {
    const lab = LABS[labId];
    const labPath = path.join(LABS_DIR, lab.composeDir);
    const hasCompose = fs.existsSync(path.join(labPath, 'docker-compose.yml'));

    if (hasCompose) return destroyComposeLab(userId, labId);

    const containerName = getContainerName(userId, labId);
    const container = await findContainer(containerName);

    if (!container) {
        console.log(`[Docker] Introuvable (déjà détruit ?) : ${containerName}`);
        return false;
    }

    try { await container.stop({ t: 5 }); } catch (e) { }
    await container.remove({ force: true });
    console.log(`[Docker] Détruit : ${containerName}`);
    return true;
}

async function destroyComposeLab(userId, labId) {
    const lab = LABS[labId];
    const labPath = path.join(LABS_DIR, lab.composeDir);
    const containerName = getContainerName(userId, labId);

    execSync(
        `docker compose -p ${containerName} down --remove-orphans --volumes`,
        { cwd: labPath }
    );

    console.log(`[Docker] Compose détruit : ${containerName}`);
    return true;
}

// ── Status ────────────────────────────────────────────────────────────

async function getLabStatus(userId, labId) {
    const containerName = getContainerName(userId, labId);
    const container = await findContainer(containerName);

    if (!container) return { running: false };

    const info = await container.inspect();
    return {
        running: info.State.Running,
        containerName,
        port: getPort(userId, labId),
        url: buildUrl(getPort(userId, labId)),
        startedAt: info.State.StartedAt,
    };
}

// ── Cleanup ───────────────────────────────────────────────────────────

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

// ── Helpers ───────────────────────────────────────────────────────────

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

module.exports = { spawnLab, destroyLab, getLabStatus, cleanupExpired };