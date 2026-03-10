// src/docker.js
const Dockerode = require('dockerode');
const path = require('path');
const { LABS, getPort, getContainerName } = require('./labs');

const docker = new Dockerode({ socketPath: '/var/run/docker.sock' });
const LABS_DIR = process.env.LABS_DIR || '../../attacks';
const NETWORK = process.env.DOCKER_NETWORK || 'lab-network';
const BASE_HOST = process.env.BASE_HOST || 'localhost';

const { execFileSync } = require('child_process');
const fs = require('fs');
const DOCKER_BIN = process.env.DOCKER_BIN || '/usr/bin/docker';

async function waitForPort(port, retries = 15, delayMs = 2000) {
    const net = require('net');

    for (let i = 0; i < retries; i++) {
        const available = await new Promise(resolve => {
            const socket = new net.Socket();
            socket.setTimeout(1000);
            socket
                .on('connect', () => { socket.destroy(); resolve(true); })
                .on('error', () => { socket.destroy(); resolve(false); })
                .on('timeout', () => { socket.destroy(); resolve(false); })
                .connect(port, '127.0.0.1');
        });

        if (available) {
            console.log(`[Docker] Port ${port} prêt après ${i + 1} tentative(s)`);
            return true;
        }

        console.log(`[Docker] Port ${port} pas encore prêt, attente ${delayMs}ms... (${i + 1}/${retries})`);
        await new Promise(r => setTimeout(r, delayMs));
    }

    console.warn(`[Docker] Port ${port} toujours indisponible après ${retries} tentatives`);
    return false;
}

// ── Spawn ─────────────────────────────────────────────────────────────

async function spawnLab(userId, labId) {
    const lab = LABS[labId];
    const labPath = path.join(LABS_DIR, lab.composeDir);

    console.log(`[Docker] labId     = ${labId}`);
    console.log(`[Docker] lab.compose   = ${lab.compose}`);
    console.log(`[Docker] labPath   = ${labPath}`);

    if (lab.compose) return spawnComposeLab(userId, labId, labPath);
    return spawnSingleLab(userId, labId);
}

async function spawnSingleLab(userId, labId) {
    const lab = LABS[labId];
    const containerName = getContainerName(userId, labId);
    const port = getPort(userId, labId);
    await waitForPort(port);
    const imageName = lab.image || `sec-lab-${labId}`;

    const existing = await findContainer(containerName);
    if (existing) {
        const info = await existing.inspect();
        if (!info.State.Running) await existing.start();
        console.log(`[Docker] Conteneur réutilisé : ${containerName}`);
        return { containerName, port, url: buildUrl(port, lab) };
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
    return { containerName, port, url: buildUrl(port, lab) };
}

async function spawnComposeLab(userId, labId, labPath) {
    const lab = LABS[labId];
    const port = getPort(userId, labId);
    const containerName = getContainerName(userId, labId);

    console.log(`[Docker] Compose spawn : ${containerName} → port ${port}`);

    console.log(`[Docker] labPath absolu = ${path.resolve(labPath)}`);
    console.log(`[Docker] DOCKER_BIN = ${DOCKER_BIN}`);
    console.log(`[Docker] cwd existe = ${fs.existsSync(labPath)}`)

    const env = {
        ...process.env,
        PATH: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        USER_ID: String(userId),
        LAB_PORT: String(port),
        VULN_PORT: String(port)
    };
    const urls = { main: buildUrl(port, lab) };

    if (lab.extraPorts) {
        for (const [name, offset] of Object.entries(lab.extraPorts)) {
            env[`${name.toUpperCase()}_PORT`] = String(port + offset);
            urls[name] = buildUrl(port + offset, lab);
        }
    }

    execFileSync(
        DOCKER_BIN,
        ['compose', '-p', containerName, 'up', '-d'],
        { cwd: labPath, env }
    );

    // Attendre que le service principal soit prêt
    await waitForPort(port);

    // Attendre aussi les ports supplémentaires si nécessaire
    if (lab.extraPorts) {
        const waitPromises = [];
        for (const [, offset] of Object.entries(lab.extraPorts)) {
            waitPromises.push(waitForPort(port + offset));
        }
        await Promise.all(waitPromises);
    }

    return { containerName, port, url: buildUrl(port, lab), urls };
}

// ── Destroy ───────────────────────────────────────────────────────────

async function destroyLab(userId, labId) {
    const lab = LABS[labId];

    if (lab.compose) return destroyComposeLab(userId, labId);

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

    execFileSync(
        DOCKER_BIN,
        ['compose', '-p', containerName, 'down', '--remove-orphans', '--volumes'],
        { cwd: labPath }
    );

    console.log(`[Docker] Compose détruit : ${containerName}`);
    return true;
}

// ── Status ────────────────────────────────────────────────────────────

async function getLabStatus(userId, labId) {
    const lab = LABS[labId];

    if (lab.compose) return getComposeLabStatus(userId, labId);

    // Cas single container
    const containerName = getContainerName(userId, labId);
    const container = await findContainer(containerName);

    if (!container) return { running: false };

    const info = await container.inspect();
    return {
        running: info.State.Running,
        containerName,
        port: getPort(userId, labId),
        url: buildUrl(getPort(userId, labId), lab),
        startedAt: info.State.StartedAt,
    };
}

async function getComposeLabStatus(userId, labId) {
    const projectName = `${labId}-u${userId}`;
    const lab = LABS[labId];

    try {
        const result = execFileSync(
            DOCKER_BIN,
            ['compose', '-p', projectName, 'ps', '--format', 'json'],
            { cwd: labPath, encoding: 'utf8' }
        );

        // Docker retourne un JSON par ligne
        const containers = result.trim().split('\n')
            .filter(Boolean)
            .map(line => {
                try { return JSON.parse(line); }
                catch (e) { return null; }
            })
            .filter(Boolean);

        if (containers.length === 0) return { running: false };

        const anyRunning = containers.some(c =>
            c.State === 'running' || c.Status?.startsWith('Up')
        );
        const allRunning = containers.every(c =>
            c.State === 'running' || c.Status?.startsWith('Up')
        );

        return {
            running: anyRunning,
            allHealthy: allRunning,
            port: getPort(userId, labId),
            url: buildUrl(getPort(userId, labId), lab),
        };

    } catch (e) {
        // Le projet compose n'existe pas ou erreur
        return { running: false };
    }
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

function buildUrl(port, lab) {
    const protocol = lab?.https ? 'https' : 'http';
    return `${protocol}://${BASE_HOST}:${port}`;
}

module.exports = { spawnLab, destroyLab, getLabStatus, cleanupExpired };