#!/bin/bash

echo "📦 Création de la config VS Code pour Moodle..."

# Aller à la racine du projet (tu peux adapter ce chemin si besoin)
cd /var/www/moodle-dev   || { echo "❌ Erreur : dossier Moodle introuvable"; exit 1; }

# Créer .vscode si nécessaire
mkdir -p .vscode

# Créer settings.json
cat > .vscode/settings.json <<EOL
{
  "php.validate.executablePath": "/usr/local/php-8.4.7/bin/php",
  "intelephense.environment.phpVersion": "8.4.7",
  "intelephense.files.exclude": [
    "**/vendor/**",
    "**/.git/**",
    "**/node_modules/**",
    "**/moodledata/**",
    "**/cache/**"
  ],
  "intelephense.files.maxMemory": 4096,
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode"
}
EOL

# Créer launch.json
cat > .vscode/launch.json <<EOL
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "log": true
    }
  ]
}
EOL

# Créer .eslintrc.json
cat > .eslintrc.json <<EOL
{
  "env": {
    "browser": true,
    "es2021": true
  },
  "extends": "eslint:recommended",
  "parserOptions": {
    "ecmaVersion": "latest",
    "sourceType": "module"
  },
  "rules": {
    "semi": ["error", "always"],
    "quotes": ["error", "single"]
  }
}
EOL

# Créer .prettierrc
cat > .prettierrc <<EOL
{
  "semi": true,
  "singleQuote": true,
  "printWidth": 100
}
EOL

# Créer .editorconfig
cat > .editorconfig <<EOL
root = true

[*]
charset = utf-8
indent_style = space
indent_size = 4
end_of_line = lf
insert_final_newline = true
trim_trailing_whitespace = true
EOL

echo "✅ Fichiers VS Code générés avec succès !"
