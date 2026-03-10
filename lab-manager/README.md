# ⚙️ Lab Manager

Microservice Node.js qui gère le cycle de vie des conteneurs Docker des labs de sécurité.

## Démarrage rapide (VPS)

```bash
# 1. Copier et configurer
cp .env.example .env
nano .env   # Renseigner BASE_HOST et API_SECRET

# 2. Créer le réseau Docker partagé
docker network create lab-network

# 3. Builder les images des labs
bash scripts/build_lab.sh all

# 4. Démarrer le Lab Manager
docker compose up -d --build

# 5. Vérifier
curl http://localhost:4000/api/health
```

## Structure

```
lab-manager/
├── src/
│   ├── index.js      # Serveur Express
│   ├── routes.js     # Endpoints API
│   ├── docker.js     # Gestion des conteneurs
│   ├── labs.js       # Catalogue des labs
│   ├── db.js         # SQLite (sessions)
│   ├── middleware.js # Auth X-API-Key
│   └── cleanup.js    # Nettoyage automatique
├── scripts/
│   ├── setup.sh      # Installation VPS complète
│   └── build_lab.sh  # Builder les images
├── INTEGRATION.md    # Guide pour l'autre développeur
├── Dockerfile
├── docker-compose.yml
└── .env.example
```

## Ajouter un nouveau lab

1. Créer le dossier `$LABS_DIR/nom_lab/` avec son `Dockerfile`
2. Ajouter une entrée dans `src/labs.js` :
```js
monlab: {
    id: 'monlab', title: 'Mon Lab', portSuffix: 6,
    composeDir: 'monlab', exercises: 3,
}
```
3. Builder l'image : `bash scripts/build_lab.sh monlab`
4. C'est tout — le lab est disponible via l'API immédiatement.

## Logs

```bash
docker compose logs -f lab-manager
```
