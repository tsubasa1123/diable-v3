# 🔓 XSS Lab — Guide de démarrage

> **Projet académique** — Environnement Docker isolé  
> ⚠️ Usage strictement éducatif. Ne reproduire ces attaques que dans ce lab.

---

## 🚀 Lancer le lab en 3 étapes

### Étape 1 — Ouvrir un terminal

- **Windows** : cherche "PowerShell" ou "CMD" dans le menu Démarrer
- **macOS** : ouvre "Terminal" (Applications → Utilitaires → Terminal)
- **Linux** : Ctrl+Alt+T

### Étape 2 — Se placer dans le dossier du lab

```bash
# Remplace le chemin par l'endroit où tu as décompressé le dossier
cd chemin/vers/xss-lab
```

### Étape 3 — Lancer Docker

```bash
docker compose up --build -d
```

Tu devrais voir des lignes défiler, puis :
```
✔ Container xss-lab  Started
```

### Accéder au lab

Ouvre ton navigateur et va sur : **http://localhost:8080**

---

## ❓ Problèmes fréquents

### "docker: command not found"
→ Docker n'est pas installé. Télécharge [Docker Desktop](https://www.docker.com/products/docker-desktop/) et relance.

### "Port 8080 already in use"
→ Un autre programme utilise le port. Modifie le fichier `docker-compose.yml`, ligne `"8080:80"` → `"8181:80"`, puis accède via `http://localhost:8181`.

### La page ne s'affiche pas
→ Attends 10 secondes et recharge. Si toujours rien :
```bash
docker compose logs
```

---

## 🛑 Arrêter le lab

```bash
docker compose down
```

---

## 📁 Structure du projet

```
xss-lab/
├── docker-compose.yml   # Configuration Docker
├── Dockerfile           # Construction de l'image
├── README.md            # Ce guide
└── app/
    └── index.php        # Application vulnérable (intentionnellement)
```
