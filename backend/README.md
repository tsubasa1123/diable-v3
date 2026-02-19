# Backend - DIABLE v3.0

**Work Package 1** - API de Gestion

---

##  Description

API REST pour la gestion de la plateforme DIABLE :
- Authentification et autorisation
- Gestion des labs (démarrage, arrêt, reset)
- Suivi de progression des utilisateurs
- Interface avec l'orchestrateur Kubernetes

---

##  Technologies

- Node.js 18
- Express.js
- PostgreSQL
- Prisma ORM
- JWT Authentication
- Docker

---

##  Installation

```bash
cd backend
npm install
npm run dev
```

---

##  Structure

```
backend/
├── src/
│   ├── controllers/   # Logique métier
│   ├── models/       # Modèles Prisma
│   ├── routes/       # Endpoints API
│   ├── middleware/   # Auth, validation
│   └── services/     # Services externes
├── config/
├── prisma/
└── package.json
```

---

##  Endpoints API

### Authentification
- `POST /api/auth/login`
- `POST /api/auth/register`
- `POST /api/auth/logout`

### Labs
- `GET /api/labs` - Liste des labs
- `POST /api/labs/:id/start` - Démarrer un lab
- `POST /api/labs/:id/stop` - Arrêter un lab
- `POST /api/labs/:id/reset` - Réinitialiser un lab

### Progression
- `GET /api/progress/:userId` - Progression utilisateur
- `POST /api/progress/flag` - Valider un flag

---

##  Contact

**Responsable WP1:** Lucien et Tarik  
**Email:** wp1@diable-project.fr
