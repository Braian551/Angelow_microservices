# Imagen base oficial de Node.js con Alpine Linux (ligera y segura)
FROM node:20-alpine

# Directorio de trabajo dentro del contenedor
WORKDIR /app

# Copiar solo los archivos de dependencias primero para aprovechar caché de Docker
COPY package*.json ./

# Instalar dependencias de producción (excluye devDependencies)
RUN npm install --omit=dev

# Copiar todo el código fuente al contenedor
COPY . .

# Exponer el puerto en el que el servicio escuchará conexiones
EXPOSE 8080

# Comando por defecto para ejecutar el servicio al iniciar el contenedor
CMD ["npm", "start"]
