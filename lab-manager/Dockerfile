FROM node:20-alpine

# Installer Docker CLI pour piloter le daemon Docker de l'hôte
RUN apk add --no-cache docker-cli

WORKDIR /app

COPY package*.json ./
RUN npm install --production

COPY src/ ./src/

RUN mkdir -p data

EXPOSE 3000

CMD ["node", "src/index.js"]
